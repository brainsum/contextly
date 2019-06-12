<?php

namespace Drupal\contextly;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\contextly\Contextly\ContextlyDrupalKit;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\node\NodeInterface;

/**
 * Class ContextlyBaseService.
 */
class ContextlyBaseService implements ContextlyBaseServiceInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function settingsValidateApiKey(string $key): array {
    // Parse string key into an array.
    $key = explode('-', $key, 2);
    if (count($key) !== 2) {
      return [FALSE, t('API key is incorrect.')];
    }

    // Convert it into associative array for later use.
    $key = array_combine(['appID', 'appSecret'], $key);

    try {
      // Check API key. Create API client with isolated session.
      $settings = ContextlyDrupalKit::getDefaultSettings();
      foreach ($key as $name => $value) {
        $settings->{$name} = $value;
      }
      $kit = new ContextlyDrupalKit($settings);
      $session = $kit->newApiSessionIsolated();
      $api = $kit->newApi($session);

      // Failed authorization should throw an exception at this point.
      $api->testCredentials();

      return array(TRUE, $key);
    }
    catch (Exception $e) {
      return array(FALSE, t('Test API request failed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsCpTokenValue(string $type): string {
    return 'contextly/cp/' . $type;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSetApiKeyTokenValue() {
    return "contextly/set-api-key";
  }

  /**
   * {@inheritdoc}
   */
  public function settingsCpTourRedirect() {
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $this->container->get('request_stack')->getMasterRequest();
    if ($request) {
      // Cleanup destination in any case.
      $request->query->remove('destination');

      $token = $request->query->get('token');
      if (empty($token)) {
        throw new NotFoundHttpException();
      }

      /** @var \Drupal\Core\Access\CsrfTokenGenerator $token_generator */
      $token_generator = $this->container->get('csrf_token');
      $token_value = $this->settingsCpTokenValue('tour');
      if (!$token_generator->validate($token, $token_value)) {
        $this->container->get('messenger')
          ->addWarning(t('Something went wrong. Please try again.'));
        return new RedirectResponse(Url::fromUri('/admin/config/content/contextly'));
      }

      return $this->settingsUrl('/tour');
      return new RedirectResponse(Url::fromUri('/tour'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSetApiKeyRedirect() {
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $this->container->get('request_stack')->getMasterRequest();
    if ($request) {
      // Cleanup destination in any case.
      $request->query->remove('destination');

      $token = $request->query->get('token');
      $api_key = $request->query->get('api_key');
      if (empty($token) || empty($api_key)) {
        throw new NotFoundHttpException();
        return new RedirectResponse(Url::fromRoute('contextly.contextly_admin_form'));
      }

      /** @var \Drupal\Core\Access\CsrfTokenGenerator $token_generator */
      $token_generator = $this->container->get('csrf_token');
      $token_value = $this->settingsSetApiKeyTokenValue();
      if (!$token_generator->validate($token, $token_value)) {
        $this->container->get('messenger')
          ->addWarning(t('Something went wrong. Please try again.'));
        return new RedirectResponse(Url::fromRoute('contextly.contextly_admin_form'));
      }

      list($success, $result) = $this->settingsValidateApiKey($api_key);
      if ($success) {
        $this->container->get('state')->set('api_key', $result);
        $this->settingsResetSharedToken();
        $this->container->get('messenger')
          ->addStatus(t('Your API key has been successfully updated.'));
      }
      else {
        $this->container->get('messenger')->addError($result);
      }

      return new RedirectResponse(Url::fromRoute('contextly.contextly_admin_form'));
    }
  }

  /**
   * Returns Contextly settings URL of specified type.
   *
   * @param string $type
   * @param null|string $access_token
   *   Is used to build direct login URL when specified.
   *
   * @return string
   */
  public function settingsUrl($type) {
    global $base_url;
    $site_name = $this->container->get('config.factory')
        ->get('system.site')->get('name');
    $token_value = $this->settingsSetApiKeyTokenValue();
    /** @var \Drupal\Core\Access\CsrfTokenGenerator $token_generator */
    $token_generator = $this->container->get('csrf_token');
    $token = $token_generator->get($token_value);
    $options = [
      'query' => [
        'token' => $token,
      ],
      'absolute' => TRUE,
    ];
    $url = Url::fromRoute('contextly.admin_controller_set_api_key', [], $options);
    $query = [
      'type' => $type,
      'blog_url' => $base_url,
      'blog_title' => $site_name,
      'cms_settings_page' => $url->toString(),
    ];

    // Try to get the auth token and use direct login.
    try {
      $token = ContextlyDrupalKit::getInstance()
        ->newApi()
        ->getAccessToken();
      $query += array(
        'contextly_access_token' => $token,
      );
    }
    catch (\Exception $e) {
      // Just silently fail.
    }

    $path = $this->contextlyServerUrl('cp') . 'cms-redirect';
    $url = Url::fromUri($path, ['query' => $query])->toString();
    $redirect = new TrustedRedirectResponse($url);
    return $redirect->send();
  }

  /**
   * Returns base URL of the Contextly server.
   *
   * URL depends on current mode: dev or production.
   *
   * @param string $server_type
   *   Possible types:
   *   - main: primary server with Contextly admin UI
   *   - api: server for API calls
   *   - cp: control panel (secure variant of the main server)
   *
   * @return string
   *
   * @todo Use the kit for this.
   */
  public function contextlyServerUrl($server_type,
    $secure = TRUE): string {
    $scheme = $secure ? 'https:' : 'http:';
    $server_mode = $this->container->get('config.factory')
        ->get('contextly.contextlyadmin')->get('server_mode') ?: 'dev';
    $servers = $this->contextlyServers($scheme);
    if (!empty($servers[$server_mode][$server_type])) {
      return $servers[$server_mode][$server_type];
    }

    return '';
  }

  /**
   * 
   * @param string $scheme
   * @return array
   */
  public function contextlyServers(string $scheme): array {
    return [
      'dev' => [
        'main' => "$scheme//dev.contextly.com/",
        'cp' => "$scheme//dev.contextly.com/",
        'api' => "$scheme//devrest.contextly.com/"
      ],
      'live' => [
        'main' => "$scheme//contextly.com/",
        'cp' => "$scheme//contextly.com/",
        'api' => "$scheme//rest.contextly.com/",
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsResetSharedToken() {
    ContextlyDrupalKit::getInstance()
      ->newApiSession()
      ->removeSharedToken();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeContextlyIsDisabled(EntityInterface $node): bool {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->container->get('database')
      ->select('contextly_node_settings', 'cns');
    $query->fields('cns', ['disabled'])
      ->condition('cns.nid', $node->id())
      ->condition('cns.vid', $node->getRevisionId());
    $result = $query->execute()->fetchField();

    return empty($result);
  }

  /**
   * 
   * @return type
   */
  public function getApiKey(): array {
    $config = $this->container->get('config.factory')
      ->get('contextly.settings');
    $key = explode('-', $config->get('api_key'));
    if (count($key) !== 2) {
      return [];
    }
    // Convert it into associative array for later use.
    return array_combine(['appID', 'appSecret'], $key);
  }

  /**
   * Returns TRUE if API key was set.
   *
   * @return bool
   */
  public function isApiKeySet(): bool {
    return !empty($this->getApiKey());
  }

  /**
   * Returns TRUE if passed node type is Contextly-enabled.
   *
   * @param string $type_name
   *
   * @return bool
   */
  public function isNodeTypeEnabled(string $type_name): bool {
    $config = $this->container->get('config.factory')
      ->get('contextly.contextlynodetypesadmin');
    if ($config->get('contextly_all_node_types')) {
      return TRUE;
    }

    $enabled_types = $config->get('contextly_node_types');
    return in_array($type_name, $enabled_types, TRUE);
  }

  /**
   * Saves Contextly settings of the node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   */
  public function saveNodeRevisionSettings(EntityInterface $node) {
    $fields = array(
      'disabled' => (int) $this->nodeContextlyIsDisabled($node),
    );
    $key = array(
      'nid' => $node->id(),
      'vid' => $node->getRevisionId(),
    );

    $this->container->get('database')->merge('contextly_node_settings')
      ->key($key)
      ->fields($fields)
      ->execute();
  }

  /**
   * Logs passed exception using Drupal watchdog.
   *
   * @param \Exception $exception
   *   The exeption class.
   * @param string $message
   *   The message.
   */
  public function watchdogException(\Exception $exception,
    string $message = NULL) {
    $details = (string) $exception;
    if (isset($message)) {
      $details = "{$message}\n\n{$details}";
    }
    $details = nl2br($details);
    $this->container->get('logger.factory')->get('contextly')->error($details);
  }

  /**
   * Callback for both node update and insert.
   *
   * Send node of enabled type to the Contextly.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   */
  public function nodeChanged(EntityInterface $node) {
    if ($this->isApiKeySet() && $this->isNodeTypeEnabled($node->bundle())) {
      try {
        ContextlyDrupalKit::getInstance()
          ->newDrupalNodeEditor()
          ->putNode($node);
      }
      catch (ContextlyKitException $e) {
        $this->watchdogException($e, 'Unable to send the node to the Contextly');
        if ($this->container->get('current_user')->hasPermission('manage contextly links')) {
          $this->container->get('messenger')
            ->addError('Unable to send the node to the Contextly. See log for details.');
        }
      }
    }
  }

  /**
   * Delete the contextly settings of the given node revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   */
  public function removeNodeRevisionSettings(EntityInterface $node) {
    $this->container->get('database')->delete('contextly_node_settings')
      ->condition('vid', $node->vid)
      ->execute();
  }

  /**
   * Returns the list of Contextly-enabled node types (machine names).
   *
   * @return array
   */
  public function getEnabledTypes() {
    $config = $this->container->get('config.factory')
      ->get('contextly.contextlynodetypesadmin');
    $all = $config->get('contextly_all_node_types');
    if ($config->get('contextly_all_node_types')) {
      return array_keys(node_type_get_types());
    }
    else {
      return $config->get('contextly_node_types');
    }
  }

  /**
   * Returns field names of selected types for the passed node type.
   *
   * @param string $node_type
   * @param array $field_types
   *
   * @return array
   */
  public function getNodeTypeFields(string $node_type,
    array $field_types) {
    $fields = [];
    $instances = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('node', $node_type);
    foreach ($instances as $field_definition) {
      if ($field_definition instanceof FieldConfig &&
        (in_array($field_definition->getType(), $field_types, TRUE) ||
        in_array($field_definition->getSetting('handler'), $field_types, TRUE))) {
        $fields[$field_definition->getName()] = $field_definition->getLabel();
      }
    }
    return $fields;
  }

  /**
   * Returns the list of field instances on Contextly-enabled node types.
   *
   * @param array $field_types
   *
   * @return array
   *   List of field names indexed by node type.
   */
  public function settingsGetAvailableFields(array $field_types) {
    $enabled_types = $this->getEnabledTypes();
    $available_fields = array();
    foreach ($enabled_types as $node_type) {
      $node_type_fields = $this->getNodeTypeFields($node_type, $field_types);
      if (!empty($node_type_fields)) {
        $available_fields[$node_type] = $node_type_fields;
      }
    }
    return $available_fields;
  }

  /**
   * Returns JS settings required for the widgets.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *
   * @return array
   */
  public function nodeWidgetsSettings(EntityInterface $node): array {
    $api_key = $this->getApiKey();
    $app_id = '';
    if (isset($api_key['appID'])) {
      $app_id = $api_key['appID'];
    }

    $isHttps = ContextlyDrupalKit::getInstance()->isHttps();

    return [
      'contextlyWidgets' => [
        'version' => CONTEXTLY_CLIENT_VERSION,
        'appId' => $app_id,
        'apiServerURL' => $this->contextlyServerUrl('api', $isHttps),
        'mainServerURL' => $this->contextlyServerUrl('main', $isHttps),
        'renderLinkWidgets' => empty($node->contextly_disabled),
      ],
    ];
  }

  /**
   * 
   */
  public function contextlyNodeView(array &$build,
    EntityInterface $entity,
    EntityViewDisplayInterface $display,
    $view_mode) {

    if (!$this->isApiKeySet()) {
      // API key is not set.
      return;
    }

    if (!$this->isNodeTypeEnabled($entity->bundle())) {
      // Node bundle is not enable to contextly view.
      return;
    }

    // Show widget on the node view page only.
    if ($view_mode !== 'full' || !node_is_page($entity)) {
      return;
    }

    //if ($display->getComponent('contextly_widget')) {
      $build['contextly_widget'] = [
        '#weight' => 0,
        ];
      $build['contextly_widget']['#attached'] = [
        'library' => [
          'contextly/widget',
          'contextly/node-view',
        ],
        'drupalSettings' => $this->nodeWidgetsSettings($entity),
      ];
      $build['storyline_subscribe'] = [
        '#theme_wrappers' => ['container'],
        '#attributes' => [
          'id' => 'ctx-sl-subscribe',
          'class' => ['ctx-clearfix'],
        ],
      ];
      $build['module'] = [
        '#theme_wrappers' => ['container'],
        '#attributes' => [
          'id' => 'ctx-module',
          'class' => ['ctx-module-container', 'ctx-clearfix'],
        ],
      ];
    //}

    // Add Contextly meta-data to the post at this point.
    $metadata = ContextlyDrupalKit::getInstance()
      ->newDrupalNodeData($entity)
      ->getMetadata();
    $metadata_tag = array(
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'name' => 'contextly-page',
        'id' => 'contextly-page',
        'content' => json_encode($metadata),
      ],
    );
    $build['#attached']['html_head'][] = [$metadata_tag, 'contextly_metadata'];
  }

  /**
   * Formats timestamp before sending to the Contextly API.
   *
   * As a temporary solution, the date and time are formatted with website
   * default timezone for consistency independent from user timezone.
   *
   * @param int $timestamp
   *
   * @return string
   */
  function formatDate($timestamp): string {
    $system_date = $this->container->get('config.factory')->get('system.date');
    $timezone = $system_date
        ->get('timezone_default') ?: date_default_timezone_get();
    return format_date($timestamp, 'custom', 'Y-m-d H:i:s', $timezone);
  }

  /**
   * Returns field names of selected types for the passed node type.
   *
   * @param string $node_type
   * @param array $field_types
   *
   * @return array
   */
  function _contextly_get_node_type_fields($node_type,
    array $field_types) {
    $fields = array();
    $instances = field_info_instances('node', $node_type);
    foreach ($instances as $instance) {
      $field_info = field_info_field($instance['field_name']);
      if (in_array($field_info['type'], $field_types, TRUE)) {
        $fields[] = $instance['field_name'];
      }
    }
    return $fields;
  }

  public function getSettings(NodeInterface $node): array {
    global $base_url;
    return array(
      'contextlyEditor' => array(
        'token' => $this->container->get('csrf_token')
          ->get('contextly/node-edit/' . $node->id()),
        'nid' => $node->id(),
        'baseUrl' => $base_url . '/',
      ),
    );
  }

}
