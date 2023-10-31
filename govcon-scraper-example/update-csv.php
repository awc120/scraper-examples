<?php

include_once 'vendor/autoload.php';
require_once('vendor/simplehtmldom/simplehtmldom/simple_html_dom.php');

/**
 * Directories to crawl.
 */

$directories = [
  '/',
  '/2016/program/sessions',
  '/2017/program/sessions',
  '/2018/program/sessions',
  '/2019/program/sessions',
  '/2020/program/sessions',
  '/2021/program/sessions',
  '/2023',
  '/2023/announcements',
];
$sections = [];
foreach ($directories as $directory) {
  $contents = scandir('www.drupalgovcon.org' . $directory);
  foreach ($contents as $key => $content) {
    // Only want .html files.
    if (!str_ends_with($content, '.html')) {
      unset($contents[$key]);
    }
  }
  $sections[$directory] = $contents;
}

if ($file = fopen('import-content.csv', 'w')) {
  fputcsv($file, ['URL', 'Page Title', 'Page Content']);
  foreach ($sections as $directory => $section) {
    foreach ($section as $page) {
      // Remove .html appended to local url.
      $url = $directory . '/' . str_replace('.html', '', $page);
      $path = 'www.drupalgovcon.org' . $directory . '/' . $page;
      if (str_starts_with($url, '//')) $url = substr($url, 1);
      echo $url . PHP_EOL; // Show current page being processed in the console.
      $html = file_get_html($path);
      $title = str_replace(' | Drupal GovCon', '', $html->find('title', 0)->innertext);
      $content = $html->find('.mq-main', 0);
      if (is_object($content)) {
        $content = $content->innertext;
      } else {
        $content = NULL;
      }
      // Note: Can include other dom elements as individual columns if they exist.
      fputcsv($file, [$url, $title, $content]);

    }
  }
  fclose($file);
}
