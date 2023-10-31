<?php

namespace Drupal\content_crawler\Trait;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Client trait for migrations.
 *
 * @package Drupal\content_crawler\Trait
 */
trait ClientPageTitle {

  /**
   * Select page title for the current client.
   */
  public function clientPageTitleSelect($dom) {
    $data = new Crawler($dom);
    $title = $data->filter('title')->first()->innerText();
    $title = str_replace(' | Drupal GovCon', '', $title);
    return $title;
  }

}
