<?php

namespace Drupal\ai_vdb_provider_faiss\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai\AiVdbProviderPluginManager;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure FAISS DB config form.
 */
class FaissConfigForm extends ConfigFormBase {

  /**
   * The VDB Provider service.
   *
   * @var \Drupal\ai\AiVdbProviderPluginManager
   */
  protected AiVdbProviderPluginManager $vdbProviderPluginManager;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected KeyRepositoryInterface $keyRepository;

  /**
   * Constructor of the FAISS DB config form.
   *
   * @param \Drupal\ai\AiVdbProviderPluginManager $vdbProviderPluginManager
   *   The VDB Provider plugin manager.
   * @param \Drupal\key\KeyRepositoryInterface $keyRepository
   *   The key repository.
   */
  public function __construct(AiVdbProviderPluginManager $vdbProviderPluginManager, KeyRepositoryInterface $keyRepository) {
    $this->vdbProviderPluginManager = $vdbProviderPluginManager;
    $this->keyRepository = $keyRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai.vdb_provider'),
      $container->get('key.repository'),
    );
  }

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_vdb_provider_faiss.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'faiss_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::CONFIG_NAME);

    $form['index_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index Storage Path'),
      '#required' => TRUE,
      '#description' => $this->t('The file system path where FAISS indexes will be stored (e.g., "sites/default/files/faiss_indexes").'),
      '#default_value' => $config->get('index_path'),
    ];

    $form['index_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Index Type'),
      '#description' => $this->t('The type of FAISS index to use. IndexFlatL2 is good for small datasets, IndexIVFFlat for larger ones.'),
      '#options' => [
        'IndexFlatL2' => $this->t('IndexFlatL2 - Brute force L2 distance'),
        'IndexFlatIP' => $this->t('IndexFlatIP - Brute force inner product'),
        'IndexIVFFlat' => $this->t('IndexIVFFlat - IVF with exact distances'),
        'IndexIVFPQ' => $this->t('IndexIVFPQ - IVF with product quantization'),
      ],
      '#default_value' => $config->get('index_type') ?: 'IndexFlatL2',
    ];

    $form['distance_metric'] = [
      '#type' => 'select',
      '#title' => $this->t('Distance Metric'),
      '#description' => $this->t('The distance metric to use for similarity calculations.'),
      '#options' => [
        'l2' => $this->t('L2 (Euclidean) Distance'),
        'ip' => $this->t('Inner Product'),
        'cosine' => $this->t('Cosine Similarity'),
      ],
      '#default_value' => $config->get('distance_metric') ?: 'l2',
    ];

    $form['nlist'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Lists (nlist)'),
      '#description' => $this->t('For IVF indexes, the number of clusters to create. Typical values are sqrt(n) where n is the number of vectors.'),
      '#default_value' => $config->get('nlist') ?: 100,
      '#min' => 1,
      '#states' => [
        'visible' => [
          ':input[name="index_type"]' => [
            ['value' => 'IndexIVFFlat'],
            ['value' => 'IndexIVFPQ'],
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $index_path = $form_state->getValue('index_path');
    
    // Check if the directory is writable or can be created
    if (!is_dir($index_path)) {
      if (!mkdir($index_path, 0755, TRUE)) {
        $form_state->setErrorByName('index_path', $this->t('Could not create the index directory.'));
      }
    } elseif (!is_writable($index_path)) {
      $form_state->setErrorByName('index_path', $this->t('The index directory is not writable.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(static::CONFIG_NAME)
      ->set('index_path', rtrim($form_state->getValue('index_path'), '/'))
      ->set('index_type', $form_state->getValue('index_type'))
      ->set('distance_metric', $form_state->getValue('distance_metric'))
      ->set('nlist', $form_state->getValue('nlist'))
      ->save();

    parent::submitForm($form, $form_state);
  }

} 