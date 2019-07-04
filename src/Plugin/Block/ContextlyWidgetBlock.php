<?php

namespace Drupal\contextly\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'ContextlyWidgetBlock' block.
 *
 * @Block(
 *  id = "contextly_widget_block",
 *  admin_label = @Translation("Contextly widget block"),
 * )
 */
class ContextlyWidgetBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Drupal\diginomica\ContainerInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  protected $container;

  /**
   * The Drupal\contextly\ContextlyWidgetsInterface definition.
   *
   * @var \Drupal\contextly\ContextlyWidgetsInterface
   *   The contextly widgets service.
   */
  protected $contextlyWidgets;

  /**
   * Constructs a new ContextlyWidgetBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContainerInterface $container
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->container = $container;
    $this->contextlyWidgets = $container->get('contextly.widgets');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account,
    $return_as_object = FALSE) {
    $route_match = $this->container->get('current_route_match');
    if ($route_match->getRouteName() === 'entity.node.canonical' &&
      $node = $route_match->getParameter('node')) {
      /** @var \Drupal\contextly\ContextlyBaseServiceInterface $base_service */
      $base_service = $this->container->get('contextly.base');
      if ($base_service->nodeContextlyIsDisabled($node)) {
        // Node has not access to contextly.
        return AccessResult::forbidden();
      }
    }

    return parent::access($account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'widget' => 'ctx_module_container',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form,
    FormStateInterface $form_state) {
    $form['widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Select widget'),
      '#options' => $this->contextlyWidgets->getWidgetsOptions(),
      '#default_value' => $this->configuration['widget'],
      '#size' => 0,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form,
    FormStateInterface $form_state) {
    $this->configuration['widget'] = $form_state->getValue('widget');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $key = $this->configuration['widget'];
    $build['contextly_widget_block_widget'] = $this->contextlyWidgets->getWidget($key);

    return $build;
  }

}
