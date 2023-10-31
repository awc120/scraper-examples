<?php

namespace Drupal\content_crawler\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\content_crawler\Service\MigrateDataStorage;
use Drupal\content_crawler\Service\Scraper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for triggering a website crawl.
 */
class CrawlerForm extends FormBase {

  /**
   * Crawler service.
   *
   * @var \Drupal\content_crawler\Service\Scraper
   */
  protected $scraper;

  /**
   * Migrate data storage service.
   *
   * @var Drupal\content_crawler\Service\MigrateDataStorage
   */
  protected $migrateDataStorage;

  /**
   * Scraper form dependency injection.
   *
   * @param \Drupal\content_crawler\Service\Scraper $scraper
   *   Scraper service.
   * @param \Drupal\content_crawler\Service\MigrateDataStorage $migrateDataStorage
   *   Migrate data storage service.
   */
  public function __construct(Scraper $scraper, MigrateDataStorage $migrateDataStorage) {
    $this->scraper = $scraper;
    $this->migrateDataStorage = $migrateDataStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_crawler.scraper'),
      $container->get('content_crawler.migrate_data_storage')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'content_crawler_scraper_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['csv_file'] = [
      '#type' => 'file',
      '#title' => $this->t('CSV file containing URLs to scrape'),
      '#size' => 30,
    ];

    $form['page_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Individual URL'),
      '#size' => 30,
    ];

    $form['actions']['scrape_csv_urls'] = [
      '#type' => 'submit',
      '#value' => $this->t('Scrape URLs from CSV'),
      '#submit' => [[$this, 'scrapeCsv']],
    ];

    $form['actions']['scrape_url'] = [
      '#type' => 'submit',
      '#value' => $this->t('Scrape individual URL'),
      '#submit' => [[$this, 'scrapeUrl']],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo
  }

  /**
   * Scrape URLs from a CSV.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function scrapeCsv(array &$form, FormStateInterface $form_state) {
    // @todo
  }

  /**
   * Scrape a single URL.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function scrapeUrl(array &$form, FormStateInterface $form_state) {
    $source = $form_state->getValue('page_url');
    $data = $this->scraper->fetch($source);
    $this->migrateDataStorage->storeData($source, $data);
    $form_state->setRedirect('view.migrate_manager.dashboard');
  }

}
