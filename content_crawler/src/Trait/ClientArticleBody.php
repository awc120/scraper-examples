<?php

namespace Drupal\content_crawler\Trait;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Client trait for migrations.
 *
 * @package Drupal\content_crawler\Trait
 */
trait ClientArticleBody {

  /**
   * Select article body for the current client.
   */
  public function clientArticleBodySelect($dom) {
    $data = new Crawler($dom);
    $classname = '.mq-main';
    $body = $data->filter($classname)->last();
    return $body->html();
  }

}
