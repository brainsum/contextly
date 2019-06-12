<?php

namespace Drupal\contextly;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ContextlyWidgets.
 */
class ContextlyWidgets implements ContextlyWidgetsInterface {

  use StringTranslationTrait;

  /**
   * Contectly widgets definition.
   *
   * @var array
   *   The contextly widgets definition array.
   */
  protected $widgets = [];

  /**
   * Constructs a new ContextlyWidgets object.
   */
  public function __construct() {
    $this->widgets = $this->getWidgets();
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetsOptions(): array {
    $options = [];
    foreach ($this->widgets as $key => $value) {
      $options[$key] = $value['name'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget(string $key): array {
    $widget = [];
    if (isset($this->widgets[$key]['tag'])) {
      $widget = $this->widgets[$key]['tag'];
    }

    return $widget;
  }

  /**
   * Return the widgets array.
   *
   * @return array
   *   The widgets definition array.
   */
  protected function getWidgets(): array {
    return [
      'ctx_module_container' => [
        'name' => $this->t('Contextly module container'),
        'tag' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'ctx-module-container',
              'ctx-clearfix',
            ],
          ],
        ],
      ],
      'ctx_siderail_container' => [
        'name' => $this->t('Contextly siderail container'),
        'tag' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'ctx-siderail-container',
            ],
          ],
        ],
      ],
      'ctx_autosidebar_container' => [
        'name' => $this->t('Contextly autosidebar container'),
        'tag' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'ctx-autosidebar-container',
              'ctx-clearfix',
            ],
          ],
        ],
      ],
      // @todo found out what these doing.
      'ctx_subscribe_container' => [
        'name' => $this->t('*** Contextly subscribe container'),
        'tag' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'ctx-subscribe-container',
              'ctx-clearfix',
            ],
          ],
        ],
      ],
      'ctx_personalization_container' => [
        'name' => $this->t('*** Contextly personalization container'),
        'tag' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'ctx-personalization-container',
              'ctx-clearfix',
            ],
          ],
        ],
      ],
      'ctx_channel_container' => [
        'name' => $this->t('*** Contextly channel container'),
        'tag' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'ctx-channel-container',
              'ctx-clearfix',
            ],
          ],
        ],
      ],
      'ctx_social_container' => [
        'name' => $this->t('*** Contextly social container'),
        'tag' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'ctx-social-container',
              'ctx-clearfix',
            ],
          ],
        ],
      ],
      'ctx_home_personalized_container' => [
        'name' => $this->t('*** Contextly home personalized container'),
        'tag' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'ctx-home-personalized-container',
              'ctx-clearfix',
            ],
          ],
        ],
      ],
    ];
  }

}
