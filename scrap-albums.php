<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo '<pre>';

function filter_filename($name) {
    $name = str_replace(array_merge(
        array_map('chr', range(0, 31)),
        array('<', '>', ':', '"', '/', '\\', '|', '?', '*')
    ), '', $name);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $name= mb_strcut(pathinfo($name, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($name)) . ($ext ? '.' . $ext : '');
    return $name;
}

require_once __DIR__ . '/vendor/autoload.php';
$fb = new \Facebook\Facebook([
  'app_id' => '%app_id%',
  'app_secret' => '%app_secret%',
  'default_graph_version' => 'v3.2'
]);
$helper = $fb->getRedirectLoginHelper();
try {
  $accessToken = $helper->getAccessToken();
  $response = $fb->get(
    '/%group_id%/albums',
    $accessToken->getValue()
  );
  $graphNode = $response->getGraphEdge();
  foreach($graphNode as $node) {
    $zip = new ZipArchive();
    $zip_name = filter_filename($node['name'] . '.zip');
    $zip->open($zip_name, ZipArchive::CREATE);
    if(!$zip) {
      die("Error creating zip file");
    }
    echo "Creating album " . $node['name'], PHP_EOL, "=========", PHP_EOL;
    $response = $fb->get(
      '/' . $node['id'] . '/photos?fields=photos.fields(source)',
      $accessToken->getValue()
    );
    $photos = $response->getGraphEdge();
    foreach($photos as $photoNode) {
      $response = $fb->get(
        '/' . $photoNode['id'] . '?fields=images',
        $accessToken->getValue()
      );
      $photo = $response->getGraphNode();
      $index = 1;
      foreach($photo['images'] as $size) {
        $file = file_get_contents($size['source']);
        $zip->addFromString(str_pad($index, 3, '0', STR_PAD_LEFT), $file);
        echo "Adding " . $size['source'];
        $index++;
      }
      echo PHP_EOL, PHP_EOL, "Closing ZIP", PHP_EOL, PHP_EOL;
      $zip->close();
    }
  }
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}
echo '</pre>';
 ?>
