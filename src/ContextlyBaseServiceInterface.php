<?php

namespace Drupal\contextly;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\node\NodeInterface;

/**
 * Interface ContextlyBaseServiceInterface.
 */
interface ContextlyBaseServiceInterface {

  /**
   * 
   * @param string $key
   * @return array
   */
  public function settingsValidateApiKey(string $key): array;

  /**
   * 
   * @param string $type
   *   The token value type.
   *
   * @return string
   *   The token string.
   */
  public function settingsCpTokenValue(string $type): string;

  /**
   * 
   */
  public function settingsSetApiKeyTokenValue();

  /**
   * 
   */
  public function settingsCpTourRedirect();

  /**
   * 
   */
  public function settingsSetApiKeyRedirect();

  /**
   * 
   */
  public function settingsResetSharedToken();

  /**
   * 
   * @param EntityInterface $node
   */
  public function nodeChanged(EntityInterface $node);

  /**
   * 
   * @param EntityInterface $node
   */
  public function nodeContextlyIsDisabled(EntityInterface $node);

  /**
   * 
   * @param EntityInterface $node
   */
  public function saveNodeRevisionSettings(EntityInterface $node);

  public function getApiKey(): array;

  public function isApiKeySet(): bool;

  public function isNodeTypeEnabled(string $type_name): bool;

  public function getEnabledTypes();

  public function nodeWidgetsSettings(EntityInterface $node): array;

  public function contextlyNodeView(array &$build,
    EntityInterface $entity,
    EntityViewDisplayInterface $display,
    $view_mode);
  
  public function getNodeTypeFields(string $node_type, array $field_types);
  
  function formatDate($timestamp): string;
  
  
  public function getSettings(NodeInterface $node): array;
    
}
