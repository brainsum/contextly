<?php

namespace Drupal\contextly;

/**
 * Interface ContextlyWidgetsInterface.
 */
interface ContextlyWidgetsInterface {

  /**
   * Return widgets option list.
   *
   * @return array
   *   The widgets options list.
   */
  public function getWidgetsOptions(): array;

  /**
   * Return contextly widget render array.
   *
   * @param string $key
   *   The widget id.
   *
   * @return array|null
   *   The render array of widget.
   */
  public function getWidget(string $key): array;

}
