<?php

namespace Drupal\content_crawler\Service;

/**
 * Scraper service for migrated content.
 */
class Scraper {

  /**
   * Scrapes a given URL.
   *
   * @param string $url
   *   Source URL.
   *
   * @return array|string|string[]
   *   Filtered scrape data.
   */
  public function fetch($url) {
    $contents = file_get_contents($url);
    return $this->filterMarkup($contents);
  }

  /**
   * Filter markup from scrape.
   *
   * @param string $markup
   *   Raw markup from scrape.
   *
   * @return array|string|string[]
   *   Filtered markup.
   */
  private function filterMarkup($markup) {
    $markup = str_replace('<!DOCTYPE html>', '', $markup);
    $markup = str_replace('<meta charset="utf-8" />', '', $markup);
    return $markup;
  }

}
