<?php
/**
 * Created by PhpStorm.
 * User: BBN
 * Date: 12/05/2015
 * Time: 12:53
 * Environment class manages the HTTP environment and sets up the MVC variables
 * - cli
 * - post
 * - get
 * - files
 * - params
 * - url
 * It uses the preset environment variables but can also be simulated
 */

namespace bbn\mvc;
use bbn;


class environment {

  private static $initiated = false;

  private
    /**
     * An array of strings enclosed between the slashes of the requested path
     * @var null|array
     */
    $params,
    /**
     * The mode of the output (doc, html, json, txt, xml...)
     * @var null|string
     */
    $mode,
    /**
     * The request sent to the server to get the actual controller.
     * @var null|string
     */
    $url,
    /**
     * @var array $_POST
     */
    $post,
    /**
     * @var array $_GET
     */
    $get,
    /**
     * @var array $_FILES
     */
    $files,
    /**
     * Determines if it is sent through the command line
     * @var boolean
     */
    $cli,
    $new_url;

  private static function _initialize(){
    self::$initiated = true;
  }

  private function set_params($path)
  {
    if ( !isset($this->params) ){
      $this->params = [];
      $tmp = explode('/', bbn\str::parse_path($path));
      foreach ( $tmp as $t ){
        if ( !empty($t) || bbn\str::is_number($t) ){
          if ( in_array($t, bbn\mvc::$reserved, true) ){
            die('The controller you are asking for contains one of the following reserved strings: ' .
              implode(', ', bbn\mvc::$reserved));
          }
          $this->params[] = $t;
        }
      }
    }
  }

  /**
   * Change the output mode (content-type)
   *
   * @param $mode
   * @return string $this->mode
   */
  public function set_mode($mode){
    if ( router::is_mode($mode) ){
      $this->mode = $mode;
    }
    return $this->mode;
  }

  private function _init(){
    // When using CLI a first parameter can be used as route,
    // a second JSON encoded can be used as $this->post
    if ( $this->cli ){
      $this->mode = 'cli';
      $this->get_cli();
    }
    // Non CLI request
    else{
      if ( !isset($this->post) ){
        $this->get_post();
      }
      if ( count($this->post) ){
        self::_dot_to_array($this->post);
        /** @todo Remove the json parameter from the bbn.js functions */
        if ( isset($this->post['appui']) && ($this->post['appui'] !== 'json') ){
          $this->set_mode($this->post['appui']);
          unset($this->post['appui']);
        }
        else {
          unset($this->post['appui']);
          $this->set_mode(BBN_DEFAULT_MODE);
        }
        array_walk_recursive($this->post, function(&$a){
          $a = bbn\str::correct_types($a);
          return $a;
        });
      }
      else if ( count($_FILES) ){
        $this->set_mode(BBN_DEFAULT_MODE);
      }
      // If no post, assuming to be a DOM document
      else {
        $this->set_mode('dom');
      }
      if ( $this->new_url ){
        $current = $this->new_url;
      }
      else if ( isset($_SERVER['REQUEST_URI']) ){
        $current = $_SERVER['REQUEST_URI'];
      }
      if ( isset($current) &&
        ( BBN_CUR_PATH === '/' || strpos($current, BBN_CUR_PATH) !== false ) ){
        $url = explode("?", urldecode($current))[0];
        if ( BBN_CUR_PATH === '/' ){
          $this->set_params($url);
        }
        else{
          $this->set_params(substr($url, strlen(BBN_CUR_PATH)));
        }
      }
    }
    $this->url = implode('/', $this->params ?: []);
    return $this;
  }

  public function __construct($url=false){
    if ( !self::$initiated ){
      self::_initialize();
      $this->cli = (php_sapi_name() === 'cli');
      $this->_init();
    }

  }

  public function set_prepath($path){
    $path = bbn\x::remove_empty(explode('/', $path));
    if ( count($path) ){
      foreach ($path as $p){
        if ($this->params[0] === $p){
          array_shift($this->params);
          $this->url = substr($this->url, strlen($p)+1);
        }
        else {
          die("The prepath $p doesn't seem to correspond to the current path {$this->url}");
        }
      }
    }
    return true;
  }

  /**
   * Returns true if called from CLI/Cron, false otherwise
   *
   * @return boolean
   */
  public function is_cli(){
    if ( !isset($this->cli) ){
      $this->cli = (php_sapi_name() === 'cli');
    }
    return $this->cli;
  }

  public function get_url(){
    return $this->url;
  }

  public function simulate($url, $post = false, $arguments = null){
    unset($this->params);
    $this->set_params($url.(empty($arguments) ? '' : '/'.implode('/', $arguments)));
    $this->post = $post ?: null;
    $this->_init();
    $this->url = $url;
  }

  public function get_mode(){
    return $this->mode;
  }

  public function get_cli(){
    global $argv;
    if ( $this->is_cli() ){
      $this->post = [];
      if ( isset($argv[1]) ){
        $this->set_params($argv[1]);
        if ( isset($argv[2]) ){
          if ( !isset($argv[3]) && \bbn\str::is_json($argv[2]) ){
            $json = json_decode($argv[2], 1);
            // Data are "normalized" i.e. types are changed through bbn\str::correct_types
            $this->post = array_map(function ($a){
              return bbn\str::correct_types($a);
            }, $json);
          }
          else{
            for ( $i = 2, $iMax = count($argv); $i < $iMax; $i++ ){
              $this->post[] = $argv[$i];
            }
          }
        }
      }
      return $this->post;
    }
  }

  public function get_get(){
    if ( !isset($this->get) ){
      $this->get = [];
      if ( count($_GET) > 0 ){
        $this->get = array_map(function($a){
          return bbn\str::correct_types($a);
        }, $_GET);
      }
    }
    return $this->get;
  }

  private static function _set_index(array $keys, array &$arr, $val){
    $new_arr =& $arr;
    while ( count($keys) ){
      $var = array_shift($keys);
      if ( !isset($new_arr[$var]) ){
        $new_arr[$var] = count($keys) ? [] : $val;
        $new_arr =& $new_arr[$var];
      }
    }
    return $arr;
  }

  private static function _dot_to_array(&$val){
    if ( is_array($val) ){
      $to_unset = [];
      foreach ( $val as $key => $v ){
        $keys = explode('.', $key);
        if ( count($keys) > 1 ){
          self::_set_index($keys, $val, $v);
          $to_unset[] = $key;
        }
      }
      foreach ( $to_unset as $a ){
        unset($val[$a]);
      }
    }
  }

  public function get_post(){
    if ( !isset($this->post) ){
      $this->post = empty($_POST) ? json_decode(file_get_contents('php://input'), 1) : $_POST;
      if ( !$this->post ){
        $this->post = [];
      }
      else{
        $this->post = bbn\str::correct_types($this->post);
      }
    }
    return $this->post;
  }

  public function get_files(){
    if ( !isset($this->files) ){
      $this->files = [];
      // Rebuilding the $_FILES array into $this->files in a more logical structure
      if ( count($_FILES) > 0 ){
        // Some devices send multiple files with the same name
        $names = [];
        foreach ( $_FILES as $n => $f ){
          if ( is_array($f['name']) ){
            $this->files[$n] = [];
            foreach ( $f['name'] as $i => $v ){
              while ( in_array($v, $names, true) ){
                if ( !isset($j) ){
                  $j = 0;
                }
                $j++;
                $file = bbn\str::file_ext($f['name'][$i], true);
                $v = $file[0].'_'.$j.'.'.$file[1];
              }
              $this->files[$n][] = [
                'name' => $v,
                'tmp_name' => $f['tmp_name'][$i],
                'type' => $f['type'][$i],
                'error' => $f['error'][$i],
                'size' => $f['size'][$i],
              ];
              $names[] = $v;
            }
          }
          else{
            $this->files[$n] = $f;
          }
        }
      }
    }
    return $this->files;
  }

  public function get_params(){
    return $this->params;
  }
}