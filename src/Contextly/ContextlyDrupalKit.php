<?php

namespace Drupal\contextly\Contextly;

use ContextlyKit;
use ContextlyKitSettings;
use ContextlyKitApiTransportInterface;
use ContextlyKitApiResponse;
use ContextlyKitBase;
use ContextlyKitApiSessionInterface;
use ContextlyKitException;
use ContextlyKitAssetsList;
use ContextlyKitAssetsRenderer;
use ContextlyKitApiRequest;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Drupal-specific kit implementation.
 *
 * @method ContextlyDrupalApiSessionShared newApiSession()
 * @method ContextlyDrupalApiTransport newApiTransport()
 * @method ContextlyDrupalAssetsList newAssetsList()
 *
 * @method ContextlyDrupalApiSessionShared newDrupalApiSessionShared()
 * @method ContextlyDrupalApiTransport newDrupalApiTransport()
 * @method ContextlyDrupalAssetsLibraryRenderer newDrupalAssetsLibraryRenderer()
 * @method ContextlyDrupalNodeEditor newDrupalNodeEditor()
 * @method ContextlyDrupalNodeData newDrupalNodeData()
 * @method ContextlyDrupalException newDrupalException()
 */
class ContextlyDrupalKit extends ContextlyKit {

  // @todo: remove if field is exist on admin form.
  const KIT_PATH = '/../vendor/contextly';

  /**
   * @return ContextlyDrupalKit
   */
  public static function getInstance() {
    static $instance;

    if (!isset($instance)) {
      $config = self::getDefaultSettings();
      $instance = new self($config);
    }

    return $instance;
  }

  /**
   * 
   * @return ContextlyKitSettings
   */
  public static function getDefaultSettings() {
    $config = new ContextlyKitSettings();
    $drupal_config = \Drupal::service('config.factory')
      ->get('contextly.contextlyadmin');
    $config->cdn = (bool) $drupal_config->get('kit_cdn');
    $config->mode = $drupal_config->get('server_mode');

    $key = \Drupal::service('contextly.base')->getApiKey();
    if (!empty($key['appID']) && !empty($key['appSecret'])) {
      $config->appID = $key['appID'];
      $config->appSecret = $key['appSecret'];
    }

    return $config;
  }

  protected function getClassesMap() {
    $map = parent::getClassesMap();

    // Overrides.
    $map['ApiSession'] = '\Drupal\contextly\Contextly\ContextlyDrupalApiSessionShared';
    $map['ApiTransport'] = '\Drupal\contextly\Contextly\ContextlyDrupalApiTransport';
    $map['AssetsList'] = '\Drupal\contextly\Contextly\ContextlyDrupalAssetsList';

    // Drupal-specific classes.
    $map['DrupalApiSessionShared'] = '\Drupal\contextly\Contextly\ContextlyDrupalApiSessionShared';
    $map['DrupalApiTransport'] = '\Drupal\contextly\Contextly\ContextlyDrupalApiTransport';
    $map['DrupalAssetsLibraryRenderer'] = '\Drupal\contextly\Contextly\ContextlyDrupalAssetsLibraryRenderer';
    $map['DrupalNodeEditor'] = '\Drupal\contextly\Contextly\ContextlyDrupalNodeEditor';
    $map['DrupalNodeData'] = '\Drupal\contextly\Contextly\ContextlyDrupalNodeData';
    $map['DrupalException'] = '\Drupal\contextly\Contextly\ContextlyDrupalException';

    return $map;
  }

  function buildFileUrl($filepath) {
    // @todo: prepare field on admin form for library folder.
    return file_create_url(DRUPAL_ROOT . $this::KIT_PATH . '/' . $filepath);
  }

}

/**
 * Sends updated node to the Contextly service, builds JS settings.
 *
 * @property ContextlyDrupalKit $kit
 */
class ContextlyDrupalNodeEditor extends ContextlyKitBase {

  /**
   * @var ContextlyKitApi
   */
  protected $api;

  public function __construct($kit) {
    parent::__construct($kit);

    $this->api = $this->kit->newApi();
  }

  /**
   * Sends the node to Contextly.
   *
   * @param object $node
   */
  public function putNode($node) {
    $this->putNodeContent($node);
    if (\Drupal::service('module_handler')->moduleExists('taxonomy')) {
      $this->putNodeTags($node);
    }
  }

  /**
   * Sends the node text content and meta-information to Contextly.
   *
   * @param object $node
   */
  protected function putNodeContent(NodeInterface $node) {
    // Build absolute node URL.
    /** @var \Drupal\Core\Url $uri */
    $url = $node->toUrl();
    if (empty($url)) {
      throw $this->kit->newDrupalException(t('Unable to generate URL for the node #@nid.', [
        '@nid' => $node->id(),
      ]));
    }
    $url->setAbsolute();
    $node_url = $url->toString();

    // Check if post has been saved to Contextly earlier.
    $contextly_post = $this->api
      ->method('posts', 'get')
      ->param('page_id', $node->id())
      ->get();

    /** @var \Drupal\user\UserInterface $user */
    $user = $node->uid->entity;

    // TODO Take care about langcode.
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $view_builder = $entity_type_manager->getViewBuilder('node');
    $content = $view_builder->view($node, 'contextly');
    $content = render($content);

    $base_service = \Drupal::service('contextly.base');
    $post_data = array(
      'post_id' => $node->id(),
      'post_title' => $node->title->value,
      'post_date' => $base_service->formatDate($node->created->value),
      'post_modified' => $base_service->formatDate($node->changed->value),
      'post_status' => $node->status->value ? 'publish' : 'draft',
      'post_type' => $node->bundle(),
      'post_content' => $content,
      'url' => $node_url,
      'author_id' => !empty($user) ? $user->id() : '',
      'post_author' => !empty($user) ? $user->getAccountName() : '',
    );
    $this->api
      ->method('posts', 'put')
      ->extraParams($post_data);
    if (isset($contextly_post->entry)) {
      $this->api->param('id', $contextly_post->entry->id);
    }

    $this->api
      ->requireSuccess()
      ->get();
  }

  /**
   * Sends node tags to the Contextly.
   *
   * @param object $node
   */
  protected function putNodeTags(NodeInterface $node) {
    // Remove existing tags first, if any.
    // TODO: Handle pagination of the request.
    $post_tags = $this->api
      ->method('poststags', 'list')
      ->searchParam('post_id', ContextlyKitApiRequest::SEARCH_TYPE_EQUAL, $node->id())
      ->get();
    if (!empty($post_tags->list)) {
      foreach ($post_tags->list as $tag) {
        $this->api
          ->method('poststags', 'delete')
          ->param('id', $tag->id)
          ->requireSuccess();
      }
    }

    // Save new tags.
    // TODO WP Plugin sends only 3 first tags. Why?
    $tags = $this->kit
      ->newDrupalNodeData($node)
      ->getTags();
    foreach ($tags as $tag) {
      $this->api
        ->method('poststags', 'put')
        ->extraParams(array(
          'post_id' => $node->id(),
          'name' => $tag,
        ))
        ->requireSuccess();
    }

    // Make all requests at once.
    $this->api->get();
  }

}

class ContextlyDrupalApiTransport implements ContextlyKitApiTransportInterface {

  /**
   * Performs the HTTP request.
   *
   * @param string $method
   *   "GET" or "POST".
   * @param string $url
   *   Request URL.
   * @param array $query
   *   GET query parameters.
   * @param array $data
   *   POST data.
   * @param array $headers
   *   List of headers.
   *
   * @return ContextlyKitApiResponse
   */
  public function request($method,
    $url,
    $query = [],
    $data = [],
    $headers = []) {
    // Add content type to the headers.
    $headers['Content-Type'] = 'application/x-www-form-urlencoded';

    // Add query to the URL.
    $url = \Drupal\Core\Url::fromUri($url, [], [
        'external' => TRUE,
        'query' => $query,
      ])->toString();


    /** @var \GuzzleHttp\Client $client */
    $client = \Drupal::httpClient();
    $options = [
      'form_params' => $data,
      'headers' => $headers,
    ];
    /** @var \GuzzleHttp\Psr7\Response $result */
    $result = $client->request($method, $url, $options);

    try {
      // Build response for the Kit.
      $response = new ContextlyKitApiResponse();
      $response->code = $result->getStatusCode();
      $response->body = $result->getBody()->getContents();
      if ($response->code != 200) {
        $response->error = $result->getBody();
      }
    }
    catch (Exception $exc) {
      // Do nothing.
    }

    return $response;
  }

}

class ContextlyDrupalApiSessionShared extends ContextlyKitBase implements ContextlyKitApiSessionInterface {

  const TOKEN_CACHE_ID = 'contextly:access-token';
  const TOKEN_CACHE_BIN = 'cache';

  /**
   * @var \ContextlyKitApiTokenInterface
   */
  protected $token;

  /**
   * The Drupal\Core\Cache\CacheBackendInterface definition.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  public function __construct($kit) {
    parent::__construct($kit);

    $this->cache = \Drupal::service('cache.data');
    $this->token = $this->loadSharedToken();
  }

  public function loadSharedToken() {
    $cache = $this->cache->get(self::TOKEN_CACHE_ID);

    if ($cache) {
      try {
        return $this->kit->newApiToken($cache->data);
      }
      catch (ContextlyKitException $e) {
        // Just suppress the exception on a broken saved value.
        watchdog_exception('contextly', $e);
      }
    }

    // Fallback to an empty token.
    return $this->kit->newApiTokenEmpty();
  }

  /**
   * @param ContextlyKitApiTokenInterface $token
   */
  public function saveSharedToken($token) {
    $expire = $token->getExpirationDate();
    $this->cache->set(self::TOKEN_CACHE_ID, (string) $token, $expire);
  }

  public function removeSharedToken() {
    $this->cache->delete(self::TOKEN_CACHE_ID);
  }

  public function cleanupToken() {
    $this->token = $this->kit->newApiTokenEmpty();
    $this->removeSharedToken();
  }

  public function setToken($token) {
    $this->token = $token;
    $this->saveSharedToken($token);
  }

  public function getToken() {
    return $this->token;
  }

}

class ContextlyDrupalAssetsList extends ContextlyKitAssetsList {

  function buildCssPaths() {
    $css = $this->getCss();
    if (empty($css)) {
      return array();
    }

    $paths = array();
    $basePath = _contextly_kit_path() . '/' . $this->kit->getFolderPath('client') . '/';
    foreach ($css as $path) {
      $paths[$path] = $basePath . $path . '.css';
    }

    return $paths;
  }

  function buildJsPaths() {
    $js = $this->getJs();
    if (empty($js)) {
      return array();
    }

    $paths = array();
    $basePath = _contextly_kit_path() . '/' . $this->kit->getFolderPath('client') . '/';
    foreach ($js as $path) {
      $paths[$path] = $basePath . $path . '.js';
    }

    return $paths;
  }

}

/**
 * Renders Contextly Kit assets in format suitable for hook_library() entry.
 *
 * @property ContextlyDrupalAssetsList $assets
 */
class ContextlyDrupalAssetsLibraryRenderer extends ContextlyKitAssetsRenderer {

  protected function renderAssets($assetsMethod,
    $external = FALSE) {
    $uris = array_values($this->assets->{$assetsMethod}());
    if (empty($uris)) {
      return array();
    }

    $options = array();
    if ($external) {
      $options += array(
        'type' => 'external',
      );
    }
    return array_combine($uris, array_fill(0, count($uris), $options));
  }

  public function renderCss() {
    if ($this->kit->isCdnEnabled()) {
      return $this->renderAssets('buildCssUrls', TRUE);
    }
    else {
      return $this->renderAssets('buildCssPaths');
    }
  }

  public function renderJs() {
    if ($this->kit->isCdnEnabled()) {
      return $this->renderAssets('buildJsUrls', TRUE);
    }
    else {
      return $this->renderAssets('buildJsPaths');
    }
  }

  public function renderTpl() {
    // We don't support templates rendering to the Drupal library yet.
  }

  public function renderAll() {
    return array(
      'css' => $this->renderCss(),
      'js' => $this->renderJs(),
    );
  }

}

/**
 * Helper to extract different data from the node.
 *
 * @property ContextlyDrupalKit $kit
 */
class ContextlyDrupalNodeData extends ContextlyKitBase {

  /**
   * The Drupal\node\NodeInterface definition.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * 
   * @param type $kit
   * @param NodeInterface $node
   */
  public function __construct($kit,
    NodeInterface $node) {
    parent::__construct($kit);

    $this->node = $node;
  }

  /**
   * 
   * @param string $language
   * @return array
   */
  public function getMetadata(string $language = NULL): array {
    $metadata = [];
    /** @var \Drupal\contextly\ContextlyBaseServiceInterface $base_service */
    $base_service = \Drupal::service('contextly.base');

    // Basic data.
    $metadata['title'] = $this->node->getTitle();
    $metadata['type'] = $this->node->bundle();
    $metadata['post_id'] = $this->node->id();

    // Timestamps.
    $metadata['pub_date'] = $base_service->formatDate($this->node->getCreatedTime());
    $metadata['mod_date'] = $base_service->formatDate($this->node->getChangedTime());

    // Node URL.
    $metadata['url'] = $this->getUrl();

    // Author info.
    /** @var \Drupal\user\UserInterface $author */
    $author = $this->getAuthor();
    if ($author) {
      $metadata['author_id'] = $author->id();
      $metadata['author_name'] = $author->getAccountName();
      $metadata['author_display_name'] = $author->getDisplayName();
    }
    else {
      $metadata['author_id'] = 0;
      $metadata['author_name'] = \Drupal::config('user.settings')->get('anonymous');
      $metadata['author_display_name'] = $metadata['author_name'];
    }

    // Tags and categories.
    // TODO Fill categories same way as tags, but from different fields.
    $metadata['tags'] = $this->getTags();
    $metadata['categories'] = array();

    // Featured image.
    $metadata['image'] = $this->getFeaturedImageUrl($language);

    return $metadata;
  }

  protected function getAuthor() {
    $author = NULL;
    if (!empty($this->node->uid)) {
      $author = $this->node->uid->entity;
    }
    return $author;
  }

  protected function getUrl() {
    $uri = $this->node->toUrl();
    $uri->setAbsolute();
    return $uri->toString();
  }

  protected function getFeaturedImageUrl($langcode) {
    $image_url = NULL;
    if (!\Drupal::service('module_handler')->moduleExists('image')) {
      return $image_url;
    }

    /** @var \Drupal\contextly\ContextlyBaseServiceInterface $base_service */
    $base_service = \Drupal::service('contextly.base');
    $fields = $base_service
      ->getNodeTypeFields($this->node->bundle(), ['image']);
    if (empty($fields)) {
      return $image_url;
    }

    $field = key($fields);
    if (empty($langcode)) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    $translation = $this->node->getTranslation($langcode);
    /** @var \Drupal\file\FileInterface $image */
    $image = $translation->{$field}->entity;
    if (empty($image->getFileUri())) {
      return $image_url;
    }

    $uri = $image->getFileUri();
    $image_url = file_create_url($uri);
    return $image_url;
  }

  /**
   * Extracts tags list attached to the node that should be sent to Contextly.
   *
   * @return array
   */
  public function getTags() {
    $tags = array();
    if (!\Drupal::service('module_handler')->moduleExists('taxonomy')) {
      return $tags;
    }

    // Use either all available fields or selected fields only.
    /** @var \Drupal\contextly\ContextlyBaseServiceInterface $base_service */
    $base_service = \Drupal::service('contextly.base');
    $fields = $base_service
      ->getNodeTypeFields($this->node->bundle(), ['default:taxonomy_term']);

    // Collect all term IDs first.
    $tags = [];
    foreach ($fields as $field_name => $field_label) {
      if (!empty($this->node->{$field_name})) {
        // TODO: Handle multi-language fields properly.
        // For now just post all languages.
        $terms = $this->node->{$field_name}->referencedEntities();
        $tags = array_merge($tags, $terms);
      }
    }

    return $tags;
  }

}

class ContextlyDrupalException extends ContextlyKitException {
  
}
