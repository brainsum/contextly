<?php

namespace Drupal\contextly\Routing;

use Symfony\Component\Routing\Route;

/**
 * Description of ConditionalRoutes
 *
 * @author dj
 */
class ConditionalRoutes {

  /**
   * 
   * @return array
   */
  public function routes(): array {
    $routes = [];
    // Declares a single route under the name 'example.content'.
    // Returns an array of Route objects. 
    $module_handler = \Drupal::service('module_handler');
    if ($module_handler->moduleExists('taxonomy')) {
      $routes['contextly.contextly_tags_admin_form'] = new Route(
        '/admin/config/content/contextly/tags',
        [
        '_form' => '\Drupal\contextly\Form\ContextlyTagsAdminForm',
        '_title' => 'Tags'
        ],
        ['_permission' => 'access administration pages'],
        ['_admin_route' => TRUE]
      );
    }
    if ($module_handler->moduleExists('image')) {
      $routes['contextly.contextly_image_admin_form'] = new Route(
        '/admin/config/content/contextly/image',
        [
        '_form' => '\Drupal\contextly\Form\ContextlyImageAdminForm',
        '_title' => 'Image'
        ],
        ['_permission' => 'access administration pages'],
        ['_admin_route' => TRUE]
      );
    }

    return $routes;
  }

}
