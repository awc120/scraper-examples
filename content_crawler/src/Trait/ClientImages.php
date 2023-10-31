<?php

namespace Drupal\content_crawler\Trait;

/**
 * Client trait for migrations.
 *
 * @package Drupal\content_crawler\Trait
 */
trait ClientImages {

  /**
   * Select images for the current client.
   */
  public function clientImageSelect($dom, $source_domain): array {
    $images = [];
    $data = new \DOMDocument();
    $data->loadHTML($dom);
    $tags = $data->getElementsByTagName('img');
    $imported = [];
    foreach ($tags as $tag) {
      $img_src = $tag->getAttribute('src');
      if (!in_array($img_src, $imported)) {
        $imported[] = $img_src;
        $img_alt = $tag->getAttribute('alt');
        if (str_starts_with($img_src, '/') && !str_starts_with($img_src, '//')) {
          $img_src = $source_domain . $img_src;
        }
        $images[] = [
          'src' => $img_src,
          'alt' => $img_alt,
        ];
      }
    }
    return $images;
  }

}
