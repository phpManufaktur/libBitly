<?php

/**
 * libBitly
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION'))
    include(WB_PATH.'/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root.'/framework/class.secure.php')) {
    include($root.'/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

$PRECHECK['PHP_VERSION'] = array('VERSION' => '5.2.0', 'OPERATOR' => '>=');

// check utf-8
global $database;
$SQL = "SELECT `value` FROM `".TABLE_PREFIX."settings` WHERE `name`='default_charset'";
$charset = $database->get_one($SQL, MYSQL_ASSOC);

// check allow_url_open and cURL
$url_status = (ini_get('allow_url_fopen') == 1) ? 'enabled' : 'disabled';
$curl_status = function_exists('curl_init') ? 'loaded' : 'missing';
$json_status = function_exists('json_decode') ? 'loaded' : 'missing';

$PRECHECK['CUSTOM_CHECKS'] = array(
    'Default Charset' => array(
        'REQUIRED' 	=> 'utf-8',
        'ACTUAL' 		=> $charset,
        'STATUS' 		=> ($charset === 'utf-8')),
    'allow_url_open' => array(
        'REQUIRED'	=> 'enabled',
        'ACTUAL'		=> $url_status,
        'STATUS'		=> ($url_status == 'enabled')),
    'cURL extension' => array(
        'REQUIRED' => 'loaded',
        'ACTUAL' => $curl_status,
        'STATUS' => ($curl_status == 'loaded')
    ),
    'JSON extension' => array(
        'REQUIRED' => 'loaded',
        'ACTUAL' => $json_status,
        'STATUS' => ($json_status == 'loaded')
    )
);
