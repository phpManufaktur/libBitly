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

if (!defined('CFG_TIME_ZONE'))
  define('CFG_TIME_ZONE', 'Europe/Berlin');

class bitlyConfig {

  const FIELD_ACCESS_TOKEN = 'cfg_access_token';
  const FIELD_LOGIN_NAME = 'cfg_login_name';
  const FIELD_API_KEY = 'cfg_api_key';
  const FIELD_TIMESTAMP = 'cfg_timestamp';

  private $table_name = null;

  private $message = '';
  private $error = '';

  /**
   * Constructor for bitlyConfig.
  */
  public function __construct() {
    date_default_timezone_set(CFG_TIME_ZONE);
    $this->table_name = TABLE_PREFIX.'mod_bitly_config';
  } // __construct()

  /**
   * Create the database table for bitlyConfig.
   * Set error message at any SQL problem.
   *
   * @return boolean
   */
  public function createTable() {
    global $database;
    $SQL = "CREATE TABLE IF NOT EXISTS `".$this->getTableName()."` ( ".
        "`cfg_id` INT(11) NOT NULL DEFAULT '1', ".
        "`cfg_access_token` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`cfg_login_name` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`cfg_api_key` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`cfg_timestamp` TIMESTAMP, ".
        "PRIMARY KEY (`cfg_id`) ".
        " ) ENGINE=MyIsam DEFAULT CHARSET utf8 COLLATE utf8_general_ci";
    $database->query($SQL);
    if ($database->is_error()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    return true;
  } // createTable()

  /**
   * Delete the database table of bitlyConfig.
   * Set error message at any SQL problem.
   *
   * @return boolean
   */
  public function deleteTable() {
    global $database;
    $database->query('DROP TABLE IF EXISTS `'.$this->getTableName().'`');
    if ($database->is_error()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    return true;
  } // deleteTable()

  /**
   * Return the complete table name with the table prefix
   *
   * @param string $table_name
   */
  public function getTableName() {
    return $this->table_name;
  } // getTableName();

  /**
   * @return string $message
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * @param string $message
   */
  protected function setMessage($message='') {
    $this->message = $message;
  }

  /**
   * Check if $this->message is empty
   *
   * @return boolean
   */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage

  /**
   * @return string $error
   */
  public function getError() {
    return $this->error;
  }

  /**
   * @param string $error
   */
  protected function setError($error='') {
    $this->error = $error;
  }

  /**
   * Check if $this->message is empty
   *
   * @return boolean
   */
  public function isError() {
    return (bool) !empty($this->error);
  } // isMessage

} // class bitlyConfig


class bitlyAccess {

  protected $cfg = null;

  protected $access_token = '';
  protected $login_name = '';
  protected $api_key = '';

  protected $client_id = '';
  protected $client_secret = '';
  protected $redirect_uri = '';

  protected $bitly_url = 'https://api-ssl.bitly.com';
  protected $authorization_endpoint = 'https://api-ssl.bitly.com/oauth/authorize';
  protected $access_token_endpoint = 'https://api-ssl.bitly.com/oauth/access_token';

  protected static $bitly_config = '';
  private static $error = '';

  protected $curl_options = array();

  /**
   * Constructor for bitlyAccess
   */
  public function __construct() {
    self::$bitly_config = WB_PATH.'/modules/lib_bitly/config.json';
    $this->cfg = new bitlyConfig();
    $this->getConfiguration();
    $this->curl_options = array(
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_RETURNTRANSFER => true,
        );
  } // __construct()

  /**
   * @return string $error
   */
  public function getError() {
    return self::$error;
  }

  /**
   * @param string $error
   */
  protected function setError($error='') {
    self::$error = $error;
  }

  /**
   * Check if $this->message is empty
   *
   * @return boolean
   */
  public function isError() {
    return (bool) !empty(self::$error);
  } // isMessage

  /**
   * Get the basic configuration
   *
   * @return boolean
   */
  protected function getConfiguration() {
    global $database;
    if (file_exists(self::$bitly_config)) {
      // get values from bitly.json config file
      $cfg = json_decode(file_get_contents(self::$bitly_config), true);
      $this->client_id = $cfg['client_id'];
      $this->client_secret = $cfg['client_secret'];
      $this->redirect_uri = $cfg['redirect_uri'];
      if (empty($this->client_id) || empty($this->client_secret) || empty($this->redirect_uri)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
            sprintf('One or more values of the configuration file <b>%s</b> are invalid, please read <em>/modules/lib_bitly/README.md for more informations', basename(self::$bitly_config))));
        return false;
      }
      // get values from database
      $this->access_token = $database->get_one("SELECT `cfg_access_token` FROM `".$this->cfg->getTableName()."` WHERE `cfg_id`='1'");
      $this->login_name = $database->get_one("SELECT `cfg_login_name` FROM `".$this->cfg->getTableName()."` WHERE `cfg_id`='1'");
      $this->api_key = $database->get_one("SELECT `cfg_api_key` FROM `".$this->cfg->getTableName()."` WHERE `cfg_id`='1'");
      return true;
    }
    // config file does not exist
    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
        sprintf('Missing the configuration file <b>%s</b>, please read <em>/modules/lib_bitly/README.md</em> for more informations.', basename(self::$bitly_config))));
    return false;
  } // getConfiguration()

  /**
   * Check if already an access token exists
   *
   * @return boolean
   */
  public function existsAccessToken() {
    return (bool) !empty($this->access_token);
  } // existsAccessToken()

  /**
   * Set the access token
   *
   * @param string $access_token
   * @return boolean
   */
  protected function setAccessToken($access_token) {
    global $database;
    $SQL = "INSERT INTO `".$this->cfg->getTableName()."` (`cfg_id`, `cfg_access_token`) ".
      "VALUES ('1', $access_token') ON DUPLICATE KEY UPDATE `cfg_access_token`='$access_token'";
    if (!$database->query($SQL)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    return true;
  } // setAccessToken()

  /**
   * Return the access token
   *
   * @return string
   */
  public function getAccessToken() {
    return $this->access_token;
  } // getAccessToken()

  protected function getCurlOptions() {
    return $this->curl_options;
  } // getCurlOptions()

  protected function put($command) {
    $ch = curl_init();
    $options = $this->getCurlOptions();
    $options[CURLOPT_URL] = $command;
    $options[CURLOPT_POST] = true;
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $status = curl_getinfo($ch);
    curl_close($ch);
    if (($status['http_code'] == '200') || ($status['http_code'] == '201')) {
      return $result;
    }
    else {
      $this->setError(sprintf('[%s - %s] %s: %s', __METHOD__, __LINE__, $status['http_code'], $result));
      return false;
    }
  } // put()

  protected function get($command) {
    $ch = curl_init();
    $options = $this->getCurlOptions();
    $options[CURLOPT_URL] = $command;
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $status = curl_getinfo($ch);
    curl_close($ch);
    if (($status['http_code'] == '200') || ($status['http_code'] == '201')) {
      return $result;
    }
    else {
      $this->setError(sprintf('[%s - %s] %s: %s', __METHOD__, __LINE__, $status['http_code'], $result));
      return false;
    }
  }

  public function bitlyGetAuthorizationCodeURL() {
    $url = sprintf('%s%s?%s',
        'https://bitly.com',
        '/oauth/authorize',
        http_build_query(array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri
            ))
        );
    return $url;
  }

  public function bitlyGetAccessToken($code) {
    $url = sprintf('%s%s?%s',
        $this->bitly_url,
        '/oauth/access_token',
        http_build_query(array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            ))
        );
    if (false === ($result = $this->put($url))) {
      return false;
    }
    else {
      $param_array = explode('&', $result);
      $param = array();
      foreach ($param_array as $item) {
        list($key, $value) = explode('=', $item);
        $param[$key] = $value;
      }
      if (isset($param['access_token']) && isset($param['login']) && isset($param['apiKey'])) {
        global $database;
        $SQL = sprintf("INSERT INTO `%s` (`cfg_id`, `cfg_access_token`, `cfg_login_name`, `cfg_api_key`) ".
            "VALUES ('1', '%s', '%s', '%s') ON DUPLICATE KEY UPDATE ".
            "`cfg_access_token`='%s', `cfg_login_name`='%s', `cfg_api_key`='%s'",
            $this->cfg->getTableName(),
            $param['access_token'],
            $param['login'],
            $param['apiKey'],
            $param['access_token'],
            $param['login'],
            $param['apiKey']
            );
        if (!$database->query($SQL)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
          return false;
        }
        return true;
      }
      else {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 'Data incomplete!'));
        return false;
      }
    }
  } // bitlyGetAccessToekn()


  public function bitlyGetUserInfo($user) {
    $url = sprintf('%s%s?%s',
        $this->bitly_url,
        '/v3/user/info',
        http_build_query(array(
            'access_token' => $this->access_token,
            'login' => $user,
        ))
    );
    if (false === ($result = $this->get($url))) {
      return false;
    }
    else {
      $result = json_decode($result, true);
      return $result['data'];
    }
  } // bitlyGetUserInfo()

  public function bitlyGetBundlesByUser() {
    $url = sprintf('%s%s?%s',
        $this->bitly_url,
        '/v3/bundle/bundles_by_user',
        http_build_query(array(
            'access_token' => $this->access_token,
            'user' => $this->login_name,
            ))
        );
    if (false === ($result = $this->get($url))) {
      return false;
    }
    else {
      $result = json_decode($result, true);
      return $result['data']['bundles'];
    }
  } // bitlyGetBundlesByUser()

  public function bitlyGetBundleContents($bundle_link) {
    $url = sprintf('%s%s?%s',
        $this->bitly_url,
        '/v3/bundle/contents',
        http_build_query(array(
            'bundle_link' => $bundle_link,
            'access_token' => $this->access_token,
            'user' => $this->login_name
            ))
        );
    if (false === ($result = $this->get($url))) return false;
    $result = json_decode($result, true);
    if ($result['status_code'] == 200)
      return $result['data']['bundle'];
    // something went wrong, return the status code and text
    $this->setError(sprintf('[%s - %s] Error code: %d - %s', __METHOD__, __LINE__,
      $result['status_code'], $result['status_txt']));
    return false;
  } // bitlyGetBundleContents()

} // class bitlyAccess