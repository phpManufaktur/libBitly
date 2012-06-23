<?php

/**
 * libBitly
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// load CSS for the Droplet?
$load_css = (isset($css) && (strtolower($css) == 'false')) ? false : true;
if (file_exists(WB_PATH.'/modules/droplets_extension/interface.php')) {
  require_once WB_PATH.'/modules/droplets_extension/interface.php';
  if ($load_css) {
    if (!is_registered_droplet_css('bitly_get_bundle_contents', PAGE_ID))
      register_droplet_css('bitly_get_bundle_contents', PAGE_ID, 'lib_bitly', 'samples/bitlyGetBundleContents/source/bitly_get_bundle_contents.css');
  }
  elseif (is_registered_droplet_css('bitly_get_bundle_contents', PAGE_ID))
    unregister_droplet_css('bitly_get_bundle_contents', PAGE_ID);
}

// check if the libBitly is installed
if (!file_exists(WB_PATH.'/modules/lib_bitly/library.php'))
  return errorMessage('The Droplet <b>bitly_get_bundle_contents</b> needs the <a href="https://addons.phpmanufaktur.de/libBitly">libBitly library</a>, please install it!');

// check if the libWebThumbnail is installed
if (!file_exists(WB_PATH.'/modules/lib_webthumbnail/library.php'))
  return errorMessage('The Droplet <b>bitly_get_bundle_contents</b> needs the <a href="https://addons.phpmanufaktur.de/libWebThumbnail">libWebThumbnail library</a>, please install it!');

// check if Dwoo is installed
if (!file_exists(WB_PATH.'/modules/dwoo/include.php'))
  return errorMessage('The Droplet <b>bitly_get_bundle_contents</b> needs the <a href="https://addons.phpmanufaktur.de/Dwoo">Dwoo Template Engine</a>, please install it!');

require_once WB_PATH.'/modules/lib_bitly/library.php';
require_once WB_PATH.'/modules/lib_webthumbnail/library.php';

if (!class_exists('Dwoo'))
  require_once WB_PATH.'/modules/dwoo/include.php';

// initialize the template engine
global $parser;
if (!is_object($parser)) {
  $cache_path = WB_PATH.'/temp/cache';
  if (!file_exists($cache_path))
    mkdir($cache_path, 0755, true);
  $compiled_path = WB_PATH.'/temp/compiled';
  if (!file_exists($compiled_path))
    mkdir($compiled_path, 0755, true);
  $parser = new Dwoo($compiled_path, $cache_path);
}

/**
 * Return a formatted error message
 *
 * @param string $error
 * @return string
 */
function errorMessage($error) {
  return sprintf('<div class="bitly_error">%s</div>', $error);
} // errorMessage()


function getBundleContents($bundle_link) {
  global $parser;
  global $bitly;
  global $thumb_width;
  global $thumb_height;
  global $bundle_explain;
  // get the bundle content
  if (false === ($bundles = $bitly->bitlyGetBundleContents($bundle_link)))
    return errorMessage($bitly->getError());
  // init the thumbnail library
  $thumbnail = new libWebThumbnail();
  // set the bundle data
  $bundle = array(
      'image_url' => $bundles['og_image'],
      'owner' => $bundles['bundle_owner'],
      'created_at' => $bundles['created_ts'],
      'description' => $bundles['description'],
      'title' => $bundles['title'],
      'last_modified' => $bundles['last_modified_ts'],
      'link' => $bundles['bundle_link'],
      'explain' => $bundle_explain
      );
  $links = array();
  $user = array();
  // walk through the links and set the data
  foreach ($bundles['links'] as $link) {
    // get the params for the thumbnail creation
    $params = $thumbnail->getParams();
    // set the updated/changed params
    $params[libWebThumbnail::PARAM_URL] = $link['long_url'];
    $params[libWebThumbnail::PARAM_WIDTH] = $thumb_width;
    $params[libWebThumbnail::PARAM_HEIGHT] = $thumb_height;
    $params[libWebThumbnail::PARAM_PAGE_ID] = PAGE_ID;
    $params[libWebThumbnail::PARAM_ALT] = $link['title'];
    $params[libWebThumbnail::PARAM_TITLE] = $link['title'];
    $thumbnail->setParams($params);
    // get the thumbnail for this link
    if (false === ($thumb = $thumbnail->getImageTag()))
      return errorMessage($thumbnail->getError());
    // walk through the comments for this link
    $comments = array();
    foreach ($link['comments'] as $comment) {
      if (!isset($user[$comment['user']])) {
        $info = $bitly->bitlyGetUserInfo($comment['user']);
        $user[$comment['user']] = $info;
      }
      $comments[] = array(
          'text' => $comment['text'],
          'user_name' => $comment['user'],
          'user_image' => isset($user[$comment['user']]['profile_image']) ? $user[$comment['user']]['profile_image'] : null,
          'user_full_name' => isset($user[$comment['user']]['full_name']) ? $user[$comment['user']]['full_name'] : $comment['user'],
          'user_profile_link' => isset($user[$comment['user']]['profile_url']) ? $user[$comment['user']]['profile_url'] : null,
          'last_modified' => (int) $comment['lm']
          );
    } // foreach comments
    $links[$link['display_order']] = array(
        'title' => $link['title'],
        'last_modified' => $link['lm'],
        'updated_by' => $link['updated_by'],
        'short_url' => $link['link'],
        'long_url' => $link['long_url'],
        'added_by' => $link['added_by'],
        'comments' => $comments,
        'img_url' => $thumb
        );
  }
  $data = array(
      'bundle' => $bundle,
      'links' => $links
      );
  return $parser->get(WB_PATH.'/modules/lib_bitly/samples/bitlyGetBundleContents/source/bitly_get_bundle_contents.lte', $data);
} // getBundleContents

// create instance of class bitlyAccess
global $bitly;
$bitly = new bitlyAccess();
if ($bitly->isError())
  // error on initialisation
  return errorMessage($bitly->getError());

if (!isset($url))
  // need the bundle_link URL!
  return errorMessage('Please use the parameter <b>url</b> and tell <b>get_bundle_contents</b> the bundle to load from bit.ly.');

global $thumb_width;
global $thumb_height;
global $bundle_explain;

$thumb_width = (isset($width)) ? (int) $width : 200;
$thumb_height = (isset($height)) ? (int) $height : 200;
$bundle_explain = (isset($explain) && (strtolower($explain) == 'true')) ? 1 : 0;

if ($bitly->existsAccessToken()) {
  // already authenticated, get the bundle
  return getBundleContents($url);
}
elseif (isset($_GET['code'])) {
  // now we need the access token
  if (!$bitly->bitlyGetAccessToken($_GET['code']))
    return errorMessage($bitly->getError());
  // now can get the bundle
  return getBundleContents($url);
}
else {
  // get authrization code
  header('Location: ' .$bitly->bitlyGetAuthorizationCodeURL());
  die('Redirect');
}
