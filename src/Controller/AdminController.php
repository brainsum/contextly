<?php

namespace Drupal\contextly\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class CpTourController.
 */
class AdminController extends ControllerBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Cp Tour.
   *
   * @return string
   *   Return Hello string.
   */
  public function cpTour() {
    return $this->container->get('contextly.base')->settingsCpTourRedirect();
  }

  /**
   * Set Api Key.
   *
   * @return string
   *   Return Hello string.
   */
  public function setApiKey() {
    return $this->container->get('contextly.base')->settingsSetApiKeyRedirect();
  }

}
