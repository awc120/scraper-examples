<?php

namespace Drupal\content_crawler\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Data storage handler service for migrated content.
 */
class MigrateDataStorage {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Migrate data storage service constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Store initial scraped data for a give source.
   *
   * @param string $source
   *   Source of scrape.
   * @param string $data
   *   Scraped data to store.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function storeData($source, $data) {
    $existing_store = $this->findFromSource($source);

    if (!empty($existing_store)) {
      $this->updateSource(array_shift($existing_store), $data);
      return;
    }

    $node = $this->entityTypeManager->getStorage('node')
      ->create([
        'type' => 'migrated_content',
        'title' => 'Migrate: ' . parse_url($source, PHP_URL_PATH),
        'field_source_link' => $source,
        'field_scraped_data' => $data,
        'field_import_date' => date('Y-m-d\TH:i:s', time()),
      ]);
    $node->save();
  }

  /**
   * Update the scraped data for a given source and node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $existing_store
   *   Existing node.
   * @param string $data
   *   Scraped data to store.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateSource($existing_store, $data) {
    $existing_store->set('field_scraped_data', $data);
    $existing_store->set('field_import_date', date('Y-m-d\TH:i:s', time()));
    $existing_store->save();
  }

  /**
   * Find migrated content by source link.
   *
   * @param string $source
   *   Source link.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Migrated node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findFromSource($source) {
    return $this->entityTypeManager->getStorage('node')
      ->loadByProperties([
        'type' => 'migrated_content',
        'field_source_link' => $source,
      ]);
  }

}
