<?php

/**
 * @file
 * Contextly api examples.
 */

use Drupal\contextly\MetaGeneratorInterface;

/**
 * Implements hook_contextly_meta_tags_alter().
 *
 * Let modules alter the contextly meta tags.
 *
 * @param array $meta_tags
 *   The alterable meta tags array.
 * @param \Drupal\contextly\MetaGeneratorInterface $meta_generator
 *   The contextly meta tags generator.
 */
function hook_contextly_meta_tags_alter(array &$meta_tags,
  MetaGeneratorInterface $meta_generator) {
  $entity = $meta_generator->getEntity();
  if (!empty($entity->field_custom_media->entity)) {
    $media = $entity->field_custom_media->entity;
    $meta_generator->setImage('image', 'field_media_image', $media);
  }
}
