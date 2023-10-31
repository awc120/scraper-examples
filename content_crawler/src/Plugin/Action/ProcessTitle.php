<?php

namespace Drupal\content_crawler\Plugin\Action;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_crawler\Trait\ClientPageTitle;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Action description.
 *
 * @Action(
 *   id = "content_crawler_process_title",
 *   label = @Translation("Process Title"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class ProcessTitle extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use ClientPageTitle;

  /**
   * Paragraph helper.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
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
    $title = $this->clientPageTitleSelect($html);

    if ($entity->hasField('field_page_title')) {
      $entity->field_page_title = $title;
      $entity->save();
    }

    // Don't return anything for a default completion message, otherwise
    // return translatable markup.
    return $this->t('Completed titlet processing for: %node', ['%node' => $entity->id()]);
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

}
