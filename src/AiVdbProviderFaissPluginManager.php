<?php

namespace Drupal\ai_vdb_provider_faiss;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for FAISS VDB provider plugins.
 *
 * This ensures that our VdbProvider plugin is properly discovered.
 */
class AiVdbProviderFaissPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // We define the plugin namespace where our plugins are found.
    parent::__construct(
      'Plugin/VdbProvider',
      $namespaces,
      $module_handler,
      'Drupal\ai\Base\AiVdbProviderClientBase',
      'Drupal\ai\Annotation\AiVdbProvider'
    );

    // Allow other modules to alter plugin definitions.
    $this->alterInfo('ai_vdb_provider_faiss_info');
    // Cache the discovered plugins.
    $this->setCacheBackend($cache_backend, 'ai_vdb_provider_faiss_plugins');
  }

} 