<?php

namespace Drupal\ai_vdb_provider_faiss\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FAISS VDB Provider plugin annotation object.
 *
 * This ensures our plugin is properly discovered.
 *
 * @Annotation
 */
class AiVdbProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

} 