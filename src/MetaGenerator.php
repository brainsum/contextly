<?php

namespace Drupal\contextly;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class MetaGenerator.
 */
class MetaGenerator implements MetaGeneratorInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  const CONTEXTLY_VERSION = '4.0';

  /**
   * Map definition.
   *
   * @var array
   *   The map array.
   */
  protected $map;

  /**
   * The Drupal\Core\Entity\EntityInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   *   The entity which generate metatags.
   */
  protected $entity;

  /**
   * Define meta tags array.
   *
   * @var array
   *   The meta tags array.
   */
  protected $metaTags = [];

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ?EntityInterface {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->map = $this->defaultMap();
  }

  /**
   * {@inheritdoc}
   */
  public function setMetaMap(array $map) {
    $this->map = $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaMap(): array {
    return $this->map;
  }

  /**
   * {@inheritdoc}
   */
  public function createMetaTags(EntityInterface $entity) {
    $this->entity = $entity;
    $this->metaTags = [];
    foreach ($this->map as $meta_key => $definition) {
      if (is_array($definition)) {
        list($function, $class) = $definition;
      }
      else {
        $function = $definition;
        $class = $this;
      }

      call_user_func([$class, $function], $meta_key);
    }

    // Let modules modify meta tags.
    $this->container->get('module_handler')
      ->alter('contextly_meta_tags', $this->metaTags, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaTags(): array {
    return $this->metaTags;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $key) {
    $this->metaTags[$key] = $this->entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setUri(string $key) {
    $this->metaTags[$key] = $this->entity
      ->toUrl('canonical', ['absolute' => TRUE])->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedDate(string $key) {
    $this->metaTags[$key] = date('Y-m-d H:i:s', $this->entity->created->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdatedDate(string $key) {
    $this->metaTags[$key] = date('Y-m-d H:i:s', $this->entity->changed->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setType(string $key) {
    $this->metaTags[$key] = 'post';
  }

  /**
   * {@inheritdoc}
   */
  public function setId(string $key) {
    $this->metaTags[$key] = $this->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorId(string $key) {
    $this->metaTags[$key] = $this->entity->uid->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorName(string $key) {
    $this->metaTags[$key] = $this->entity->uid->entity->getUserName();
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorDisplayName(string $key) {
    $this->metaTags[$key] = $this->entity->uid->entity->getDisplayName();
  }

  /**
   * {@inheritdoc}
   */
  public function setTags(
    string $key,
    string $field_name = 'field_tags') {
    $this->metaTags[$key] = $this->getReferencedLabels($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function setCategories(
    string $key,
    string $field_name = 'field_category') {
    $this->metaTags[$key] = $this->getReferencedLabels($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function setImage(
    string $key,
    string $field_name = 'field_image',
    EntityInterface $entity = NULL) {
    $url = '';
    $alt = '';
    if (empty($entity)) {
      $entity = $this->entity;
    }
    if (!empty($entity->{$field_name}->entity) &&
      $image = $entity->{$field_name}->entity) {
      $url = file_create_url($image->getFileUri());
      $alt = $entity->{$field_name}->getValue()[0]['alt'];
    }

    $this->metaTags[$key] = $url;
    if (!empty($alt)) {
      $this->metaTags['image_alt'] = $alt;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getApiData(): ? array {
    $api_key = $this->container->get('config.factory')
      ->get('contextly.settings')->get('api_key');
    return explode('-', $api_key);
  }

  /**
   * Prepare elements array.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The elements array.
   */
  protected function getReferencedLabels(string $field_name): array {
    $elements = [];
    if (!empty($this->entity->{$field_name}->entity)) {
      foreach ($this->entity->{$field_name}->referencedEntities() as $entity) {
        $elements[] = $entity->label();
      }
    }

    return $elements;
  }

  /**
   * Add app id to meta tags.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  protected function setAppId(string $key) {
    list($app_id) = $this->getApiData();
    $this->metaTags[$key] = $app_id;
  }

  /**
   * Add version to meta tags.
   *
   * @param string $key
   *   The key from meta tag map.
   */
  protected function setVersion(string $key) {
    $this->metaTags[$key] = $this::CONTEXTLY_VERSION;
  }

  /**
   * Return the default contextly meta tag map.
   *
   * @return array
   *   The default meta tag map.
   */
  protected function defaultMap(): array {
    return [
      'title' => 'setTitle',
      'url' => 'setUri',
      'pub_date' => 'setCreatedDate',
      'mod_date' => 'setUpdatedDate',
      'type' => 'setType',
      'post_id' => 'setId',
      'author_id' => 'setAuthorId',
      'author_name' => 'setAuthorName',
      'author_display_name' => 'setAuthorDisplayName',
      'tags' => 'setTags',
      'categories' => 'setCategories',
      'image' => 'setImage',
      'app_id' => 'setAppId',
      'version' => 'setVersion',
    ];
  }

}
