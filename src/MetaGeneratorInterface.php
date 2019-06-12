<?php

namespace Drupal\contextly;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface MetaGeneratorInterface.
 */
interface MetaGeneratorInterface {

  /**
   * Return the entity which generating metagata.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity or NULL.
   */
  public function getEntity(): ?EntityInterface;

  /**
   * Let overridden meta tags map.
   *
   * @param array $map
   *   The map array.
   */
  public function setMetaMap(array $map);

  /**
   * Return the actual meta tag map.
   *
   * @return array
   *   The meta tag map array.
   */
  public function getMetaMap(): array;

  /**
   * Prepare meta tags from the entity data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which is using to prepare meta tags.
   */
  public function createMetaTags(EntityInterface $entity);

  /**
   * Return the prepared meta tags.
   *
   * @return array
   *   The meta tags array.
   */
  public function getMetaTags(): array;

  /**
   * Set contextly meta tag title.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setTitle(string $key);

  /**
   * Set contextly meta tag uri.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setUri(string $key);

  /**
   * Set contextly meta tag created date.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setCreatedDate(string $key);

  /**
   * Set contextly meta tag updated date.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setUpdatedDate(string $key);

  /**
   * Set contextly meta tag type.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setType(string $key);

  /**
   * Set contextly meta tag content id.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setId(string $key);

  /**
   * Set contextly meta tag author id.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setAuthorId(string $key);

  /**
   * Set contextly meta tag author name.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setAuthorName(string $key);

  /**
   * Set contextly meta tag author display name.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setAuthorDisplayName(string $key);

  /**
   * Set contextly meta tag tags.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setTags(string $key);

  /**
   * Set contextly meta tag categories.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  public function setCategories(string $key);

  /**
   * Set contextly meta tag image.
   *
   * @param string $key
   *   The key from meta tag map.
   * @param string $field_name
   *   The image field name.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which contains image.
   */
  public function setImage(string $key,
    string $field_name = 'field_image',
    EntityInterface $entity = NULL);

  /**
   * Return the api informations.
   *
   * @return array|null
   *   The api informations.
   */
  public function getApiData(): ? array;

}
