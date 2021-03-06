<?php
/**
 * @package bbn
 */
namespace bbn\models\cls;
use bbn;
/**
 * Data Class with Db and cache
 *
 *
 * All change of properties will be synced with the database
 *
 * @author Thomas Nabet <thomas.nabet@gmail.com>
 * @copyright BBN Solutions
 * @since July 15, 2016, 14:10:55 +0000
 * @category  Data Observer
 * @license   http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version 0.1
 */
abstract class data extends cache{

  private static
    $_bbn_reflection_class = false,
    $_bbn_protected_properties;

  protected static
    $table,
    $columns,
    $primary;

  private
    $_bbn_ok;

  protected
    $id;

  private static function _init($obj, bbn\db $db){
    if ( empty(self::$table) ){
      die("You have no table configured for your class ".get_class($obj));
    }
    if ( self::$primary = $db->get_primary(self::$table) ){
      self::$columns = $db->get_columns(self::$table);
      self::$_bbn_reflection_class = new \ReflectionObject($obj);
      self::$_bbn_protected_properties = array_map(function($a){
        return $a->getName();
      }, self::$_bbn_reflection_class->getProperties(\ReflectionProperty::IS_PROTECTED));
    }
  }

  public function __construct(db $db, $uid){
    parent::__construct($db);
    if ( !self::$_bbn_reflection_class ){
      self::_init($this, $this->db);
    }
    if ( self::$columns && $this->db->count(self::$table, [self::$primary => $uid]) ){
      $this->_bbn_ok = 1;
      $this->id = $uid;
    }
  }



  public function __get($name){
    if ( $this->is_ok() ){
      if (
        ($name !== 'db') &&
        in_array($name, self::$_bbn_protected_properties, true) &&
        method_exists($this, 'get_'.$name)
      ){
        return $this->{'get_'.$name}();
      }
      else if ( isset(self::$columns[$name]) ){
        if ( !isset($this->$name) ){
          $this->$name = $this->db->select_one(self::$table, $name, [self::$primary => $this->id]);
        }
        return $this->$name;
      }
    }
  }

  public function __set($name, $value){
    if ( $this->is_ok() ){
      if (
        ($name !== 'db') &&
        in_array($name, self::$_bbn_protected_properties, true) &&
        method_exists($this, 'set_'.$name)
      ){
        $this->{'set_'.$name}($value);
      }
      else if ( isset(self::$columns[$name]) ){
        $this->db->update(self::$table, [$name => $value], [self::$primary => $this->id]);
      }
    }
  }

  public function __isset($name){
    if ( $this->is_ok() ){
      if (
        ($name !== 'db') &&
        in_array($name, self::$_bbn_protected_properties, true) &&
        method_exists($this, 'isset_'.$name)
      ){
        return $this->{'isset_'.$name}();
      }
      else if ( isset(self::$columns[$name]) ){
        return isset($this->$name);
      }
    }
  }

  public function __unset($name){
    if ( $this->is_ok() ){
      if (
        ($name !== 'db') &&
        in_array($name, self::$_bbn_protected_properties, true) &&
        method_exists($this, 'unset_'.$name)
      ){
        $this->{'unset_'.$name}();
      }
      else if ( isset(self::$columns[$name]) ){
        $this->db->update(self::$table, [$name => self::$columns[$name]['default']], [self::$primary => $this->id]);
        $this->$name = self::$columns[$name]['default'];
      }
    }
  }

  public function is_ok(){
    return $this->_bbn_ok;
  }

  protected function update($change, $val = ''){
    if ( $this->is_ok() ){
      if ( !is_array($change) ){
        $change = [$change => $val];
      }
      return $this->db->update('$table', $change, ['$primary' => $this->$primary]);
    }
    return false;
  }
  
}