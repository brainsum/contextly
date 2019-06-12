<?php

namespace Drupal\contextly;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\contextly\Contextly\ContextlyDrupalKit;

/**
 * Class ContextlyLibraryService.
 */
class ContextlyLibraryService implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * 
   * @return array
   */
  public function addLibbraries(): array {
    $libraries = [];

    $module_path = drupal_get_path('module', 'contextly');
    $js_path = $module_path . '/js/';

    // Libraries from the Kit.
    $map = $this->getLibrariesMap();

    $kit = ContextlyDrupalKit::getInstance();
    $assets_manager = $kit->newAssetsManager();
    foreach ($map as $key => $info) {
      $info += [
        'ignore' => [],
        'library' => [],
      ];
      $info['library'] += [
        'version' => ContextlyDrupalKit::version(),
      ];

      $assets = $kit->newAssetsList();
      $assets_manager->extractPackageAssets($info['package'], $assets, $info['ignore']);
      $library = $kit->newDrupalAssetsLibraryRenderer($assets)
        ->renderAll();
      $library += $info['library'];

      $libraries[$key] = $library;
    }

    // Add runtime json2 loading to the EasyXDM.
    $assets = $kit->newAssetsList();
    $assets_manager->extractPackageAssets('libraries/json2', $assets);
    /** @var \Drupal\Component\Serialization\SerializationInterface $encoder */
    $encoder = $this->container->get('serialization.json');
    $script = [];
    foreach ($assets->buildJsUrls() as $url) {
      $script[] = 'easyXDM.DomHelper.requiresJSON(' . $encoder->encode($url) . ');';
    }
    $libraries['easy-xdm']['js'][] = [
      'type' => 'inline',
      'data' => implode("\n", $script),
    ];

    return $libraries;
  }

  /**
   * 
   * @return array
   */
  protected function getLibrariesMap(): array {
    // Libraries from the Kit.
    return [
      'editor-overlay' => [
        'package' => 'overlay-dialogs/overlay',
        'ignore' => [
          'libraries/jquery' => TRUE,
        ],
        'library' => [
          // 'title' => 'Contextly: overlay',
          'dependencies' => [
            'system/jquery',
          ],
        ],
      ],
      'easy-xdm' => [
        'package' => 'libraries/easy-xdm',
        'library' => [
          // 'title' => 'EasyXDM',
        ],
      ],
      'widget' => [
        'package' => 'widgets/page-view',
        'ignore' => [
          'libraries/jquery' => TRUE,
          'libraries/easy-xdm' => TRUE,
        ],
        'library' => [
          // 'title' => 'Contextly: widgets',
          'dependencies' => [
            'system/jquery',
            'contextly/easy-xdm',
          ],
        ],
      ],
    ];
  }

}
