<?php

namespace Drupal\content_crawler\Plugin\Action;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileRepository;
use Drupal\content_crawler\Trait\ClientImages;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Action description.
 *
 * @Action(
 *   id = "content_crawler_process_images",
 *   label = @Translation("Process Images"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class ProcessImages extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use ClientImages;

  /**
   * Paragraph helper.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File repository service.
   *
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param array $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\file\FileRepository $fileRepository
   *   File repository.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File system interface.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entityTypeManager, FileRepository $fileRepository, FileSystemInterface $fileSystem) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->fileRepository = $fileRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file.repository'),
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!$this->validEntity($entity)) {
      return $this->t('Invalid entity.');
    }

    $html = $entity->field_scraped_data->value;
    $source = $entity->field_source_link->uri;
    $source_domain = parse_url($source, PHP_URL_SCHEME) . '://' . parse_url($source, PHP_URL_HOST);
    $images = $this->clientImageSelect($html, $source_domain);
    $image_medias= $this->createImageMedias($images);

    if ($entity->hasField('field_imported_images')) {
      $entity->field_imported_images = $image_medias;
      $entity->save();
    }

    // Don't return anything for a default completion message, otherwise
    // return translatable markup.
    return $this->t('Completed image processing for: %node', ['%node' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // If certain fields are updated, access should be checked against them
    // as well.
    // @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access('update', $account, $return_as_object);
  }

  /**
   * Checks entity to see if it is a migrated content.
   */
  public function validEntity($entity) {
    if (!$entity instanceof EntityInterface) {
      return FALSE;
    }

    if (!$entity->hasField('field_scraped_data')) {
      return FALSE;
    }

    if ($entity->field_scraped_data->isEmpty()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Create paragraphs with file types from imported page.
   */
  public function createImageMedias($files) {
    $medias = [];
    foreach ($files as $file) {
      $directory = str_replace('https://www.drupalgovcon.org/', '', dirname($file['src']));
      $directory = str_replace('sites/default/files/', '', $directory);
      $file_path = 'public://' . $directory;
      $this->fileSystem->prepareDirectory($file_path, FileSystemInterface::CREATE_DIRECTORY);
      $file_name = $file_path . '/' . parse_url(basename($file['src']), PHP_URL_PATH);
      // Check if file was already imported.
      $existing_files = $this->entityTypeManager->getStorage('file')
        ->loadByProperties(['uri' => $file_name]);
      if (!empty($existing_files)) {
        $existing_file = reset($existing_files);
        $existing_medias = $this->entityTypeManager->getStorage('media')
          ->loadByProperties(['field_media_image' => $existing_file->id()]);
        $media = reset($existing_medias);
      }
      else {
        // File does not already exist, so create media item.
        $file_data = file_get_contents($file['src']);
        $drupal_file = $this->fileRepository->writeData($file_data, $file_name, FileSystemInterface::EXISTS_REPLACE);
        $media = $this->entityTypeManager->getStorage('media')
          ->create([
            'name' => $file['alt'],
            'bundle' => 'image',
            'uid' => 1,
            'status' => 1,
            'field_media_image' => [
              'target_id' => $drupal_file->id(),
              'alt' => $file['alt'],
            ],
          ]);
        $media->save();
      }
      $medias[] = $media;
    }
    return $medias;
  }

}
