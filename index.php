<?php
session_start();
ini_set('display_errors', 1);
require_once __DIR__ . '/vendor/autoload.php';
$fb = new Facebook\Facebook([
  'app_id' => '%app_id%',
  'app_secret' => '%app_secret%',
  'default_graph_version' => 'v3.2',
]);

$helper = $fb->getRedirectLoginHelper();

$loginUrl = $helper->getLoginUrl(
  'http://localhost/fb-scrap-album/scrap-albums.php',
  ['groups_access_member_info']
);

echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
