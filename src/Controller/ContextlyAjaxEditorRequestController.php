<?php

namespace Drupal\contextly\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\contextly\Contextly\ContextlyDrupalKit;

/**
 * Class ContextlyAjaxEditorRequestController.
 */
class ContextlyAjaxEditorRequestController extends ControllerBase {

  /**
   * Request.
   *
   * @return string
   *   Return Hello string.
   */
  public function request($request,
    $node) {
    $a = 1;
    //return [];
    try {
      // Build parameters list.
      $params = $_POST;
      $params['custom_id'] = $node->id();

      // Handle the request.
      $result = ContextlyDrupalKit::getInstance()
        ->newWidgetsEditor()
        ->handleRequest($request, $params);

      // Deliver results to the client.
      return new JsonResponse($result);
    }
    catch (ContextlyKitException $e) {
      _contextly_watchdog_exception($e);
      _contextly_return_internal_error();
    }
  }

  /**
   * Snippet.
   *
   * @return string
   *   Return Hello string.
   */
  public function handle($mode,
    $node) {
    // @todo: check $node why is in url.
    print ContextlyDrupalKit::getInstance()->newOverlayDialog($mode)->render();
    exit;
    return [];
  }

}
