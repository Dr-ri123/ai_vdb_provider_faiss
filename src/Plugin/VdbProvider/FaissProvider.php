<?php

namespace Drupal\ai_vdb_provider_faiss\Plugin\VdbProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\AiVdbProvider;
use Drupal\ai\Base\AiVdbProviderClientBase;
use Drupal\ai\Enum\VdbSimilarityMetrics;
use Drupal\ai_vdb_provider_faiss\Faiss;
use Drupal\key\KeyRepositoryInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'FAISS DB' provider.
 *
 * @AiVdbProvider(
 *   id = "faiss",
 *   label = @Translation("FAISS DB")
 * )
 */
class FaissProvider extends AiVdbProviderClientBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs an override for the AiVdbClientBase class to add FAISS.
   *
   * @param string $pluginId
   *   Plugin ID.
   * @param mixed $pluginDefinition
   *   Plugin definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\key\KeyRepositoryInterface $keyRepository
   *   The key repository.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\ai_vdb_provider_faiss\Faiss $client
   *   The FAISS API client.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(
    protected string $pluginId,
    protected mixed $pluginDefinition,
    protected ConfigFactoryInterface $configFactory,
    protected KeyRepositoryInterface $keyRepository,
    protected EventDispatcherInterface $eventDispatcher,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected MessengerInterface $messenger,
    protected Faiss $client,
    protected Request $request,
  ) {
    parent::__construct(
      $this->pluginId,
      $this->pluginDefinition,
      $this->configFactory,
      $this->keyRepository,
      $this->eventDispatcher,
      $this->entityFieldManager,
      $this->messenger,
    );
  }

  /**
   * Load from dependency injection container.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): AiVdbProviderClientBase|static {
    return new static(
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('key.repository'),
      $container->get('event_dispatcher'),
      $container->get('entity_field.manager'),
      $container->get('messenger'),
      $container->get('faiss.api'),
      $container->get('request_stack')->getCurrentRequest(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(): ImmutableConfig {
    return $this->configFactory->get('ai_vdb_provider_faiss.settings');
  }

  /**
   * Set key for authentication of the client.
   *
   * @param mixed $authentication
   *   The authentication (not used for FAISS).
   */
  public function setAuthentication(mixed $authentication): void {
    // FAISS doesn't require authentication since it's local
  }

  /**
   * Get FAISS client.
   *
   * @return \Drupal\ai_vdb_provider_faiss\Faiss
   *   The FAISS client.
   */
  public function getClient(): Faiss {
    $config = $this->getConnectionData();
    $this->client->setIndexPath($config['index_path']);
    $this->client->setIndexType($config['index_type']);
    $this->client->setDistanceMetric($config['distance_metric']);
    $this->client->setNlist($config['nlist']);
    return $this->client;
  }

  /**
   * Get connection data.
   *
   * @return array
   *   The connection data.
   */
  public function getConnectionData() {
    $config = $this->getConfig();
    $output['index_path'] = $this->configuration['index_path'] ?? $config->get('index_path');
    
    // Fail if index path is not set.
    if (!$output['index_path']) {
      throw new \Exception('FAISS index path is not configured');
    }
    
    $output['index_type'] = $this->configuration['index_type'] ?? $config->get('index_type') ?? 'IndexFlatL2';
    $output['distance_metric'] = $this->configuration['distance_metric'] ?? $config->get('distance_metric') ?? 'l2';
    $output['nlist'] = $this->configuration['nlist'] ?? $config->get('nlist') ?? 100;
    
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function ping(): bool {
    try {
      $config = $this->getConnectionData();
      // Check if the index directory exists and is writable
      return is_dir($config['index_path']) && is_writable($config['index_path']);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isSetup(): bool {
    if ($this->getConfig()->get('index_path')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(
    array $form,
    FormStateInterface $form_state,
    array $configuration,
  ): array {
    $form = parent::buildSettingsForm($form, $form_state, $configuration);

    // Update database name description for FAISS
    if (isset($form['database_name'])) {
      $form['database_name']['#description'] = $this->t('In FAISS, this will be used as a prefix for the index file name. Set this to something descriptive for your content, like "content" or "documents".');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(array &$form, FormStateInterface $form_state): void {
    // FAISS doesn't need server validation since it's local
    parent::validateSettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewIndexSettings(array $database_settings): array {
    $results = [];
    
    try {
      $config = $this->getConnectionData();
      
      $results['status'] = [
        'label' => $this->t('FAISS Status'),
        'info' => $this->t('Index path: @path', ['@path' => $config['index_path']]),
        'status' => is_dir($config['index_path']) ? 'ok' : 'warning',
      ];
      
      $results['index_type'] = [
        'label' => $this->t('Index Type'),
        'info' => $config['index_type'],
      ];
      
      $results['distance_metric'] = [
        'label' => $this->t('Distance Metric'),
        'info' => $config['distance_metric'],
      ];
      
    } catch (\Exception $e) {
      $results['error'] = [
        'label' => $this->t('Configuration Error'),
        'info' => $e->getMessage(),
        'status' => 'error',
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollections(string $database = 'default'): array {
    return $this->getClient()->listCollections($database);
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection(
    string $collection_name,
    int $dimension,
    VdbSimilarityMetrics $metric_type = VdbSimilarityMetrics::CosineSimilarity,
    string $database = 'default',
  ): void {
    $metric_name = match ($metric_type) {
      VdbSimilarityMetrics::EuclideanDistance => 'l2',
      VdbSimilarityMetrics::CosineSimilarity => 'cosine',
      VdbSimilarityMetrics::InnerProduct => 'ip',
    };
    $client = $this->getClient();
    $client->createCollection(
      $collection_name,
      $database,
      $dimension,
      $metric_name,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function dropCollection(
    string $collection_name,
    string $database = 'default',
  ): void {
    $this->getClient()->dropCollection($collection_name, $database);
  }

  /**
   * {@inheritdoc}
   */
  public function insertIntoCollection(
    string $collection_name,
    array $data,
    string $database = 'default',
  ): void {
    $this->getClient()->insert($collection_name, $data, $database);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFromCollection(
    string $collection_name,
    array $ids,
    string $database = 'default',
  ): void {
    $this->getClient()->delete($collection_name, $ids, $database);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFilters(QueryInterface $query): string {
    // TODO: Implement FAISS-specific filter preparation
    // This would need to convert Drupal Search API query conditions
    // into a format that FAISS can understand for metadata filtering
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function querySearch(
    string $collection_name,
    array $output_fields,
    mixed $filters = 'id not in [0]',
    int $limit = 10,
    int $offset = 0,
    string $database = 'default',
  ): array {
    $data = $this->getClient()->query(
      $collection_name,
      $output_fields,
      $filters,
      $limit,
      $offset,
      $database
    );
    return $data['data'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function vectorSearch(
    string $collection_name,
    array $vector_input,
    array $output_fields,
    QueryInterface $query,
    string $filters = '',
    int $limit = 10,
    int $offset = 0,
    string $database = 'default',
  ): array {
    // If filters are not provided, prepare them from the query.
    if (empty($filters)) {
      $filters = $this->prepareFilters($query);
    }
    
    $data = $this->getClient()->search(
      $collection_name,
      $vector_input,
      $output_fields,
      $filters,
      $limit,
      $offset,
      $database
    );
    return $data['data'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getVdbIds(
    string $collection_name,
    array $drupalIds,
    string $database = 'default',
  ): array {
    // TODO: Implement FAISS-specific ID mapping
    // This would need to map Drupal entity IDs to FAISS vector IDs
    return [];
  }

} 