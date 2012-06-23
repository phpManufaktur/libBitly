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

  if (false === ($bundles = $bitly->bitlyGetBundleContents($bundle_link)))
    return errorMessage($bitly->getError());
  $thumbnail = new libWebThumbnail();
echo "<pre>";
print_r($bundles);
echo "</pre>";
  $bundle = array(
      'image_url' => $bundles['og_image'],
      'owner' => $bundles['bundle_owner'],
      'created_at' => $bundles['created_ts'],
      'description' => $bundles['description'],
      'title' => $bundles['title'],
      'last_modified' => $bundles['last_modified_ts'],
      'link' => $bundles['bundle_link']
      );
  $links = array();
  foreach ($bundles['links'] as $link) {
    $comments = array();
    $params = $thumbnail->getParams();
    $params[libWebThumbnail::PARAM_URL] = $link['long_url'];
    $params[libWebThumbnail::PARAM_WIDTH] = $thumb_width;
    $params[libWebThumbnail::PARAM_HEIGHT] = $thumb_height;
    $thumbnail->setParams($params);
    if (false === ($thumb = $thumbnail->getImageTag()))
      return errorMessage($thumbnail->getError());
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

$thumb_width = (isset($width)) ? (int) $width : 150;
$thumb_height = (isset($height)) ? (int) $height : 150;

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
