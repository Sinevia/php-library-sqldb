<?php

// ========================================================================= //
// SINEVIA PUBLIC                                        http://sinevia.com  //
// ------------------------------------------------------------------------- //
// COPYRIGHT (c) 2008-2018 Sinevia Ltd                   All rights reserved //
// ------------------------------------------------------------------------- //
// LICENCE: All information contained herein is, and remains, property of    //
// Sinevia Ltd at all times.  Any intellectual and technical concepts        //
// are proprietary to Sinevia Ltd and may be covered by existing patents,    //
// patents in process, and are protected by trade secret or copyright law.   //
// Dissemination or reproduction of this information is strictly forbidden   //
// unless prior written permission is obtained from Sinevia Ltd per domain.  //
//===========================================================================//

namespace Sinevia;

use \OutOfRangeException;

interface IActiveRecord {

    static function getDatabase();

    static function getTableName();

    static function getKeys();
}

abstract class ActiveRecord implements IActiveRecord {

    public static function getKeys() {
        return static::$keys;
    }

    public static function getTableName() {
        return static::$table;
    }

    public static function getDatabase() {
        return \Application::getDatabase();
    }
    
    /**
     * Finds a record by the specified key(s)
     * @param mixed $key
     * @return ActiveRecord
     */
    public static function find($key) {
        $class_name = get_called_class();
        $o = new $class_name;
        $keys = func_get_args();
        $okeys = $o::getKeys();
        if (count($keys) < count($okeys)) {
            throw new RuntimeException("To find an object of class " . $class_name . " at least " . count($okeys) . " keys are needed!");
        }
        $db = $o::getDatabase();
        $db = $db->table($o::getTableName());
        for ($i = 0; $i < count($keys); $i++) {
            $db = $db->where($okeys[$i], '=', $keys[$i]);
        }
        $result = $db->selectOne();
        
        if($result===null){
            return null;
        }
        
        $o->data = $result;
        return $o;
    }

    protected function insert() {
        $db = self::getDatabase();
        $db->table($this->getTableName())->insert($this->data_changed);

        // If the primary key is Autoincrement field, populate it
        $keys = $this->getKeys();
        $primary_key = $keys[0];
        if (isset($this->data[$primary_key]) == false) {
            $primary_key_value = $db->lastInsertId();
            $this->data[$primary_key] = $primary_key_value;
        }
        $this->data_changed = array();
        return true;
    }

    protected function update() {
        $keys = $this->getKeys();
        $db = self::getDatabase();
        $db = $db->table(self::getTableName());
        for ($i = 0; $i < count($keys); $i++) {
            $db = $db->where($keys[$i], '=', $this->data[$keys[$i]]);
        }
        $result = $db->update($this->data_changed);
        if ($result != false) {
            $this->data_changed = array();
        }
        return true;
    }

    /**
     * Deletes the record
     * @return boolean
     */
    public function delete() {
        $keys = $this->getKeys();
        $db = self::getDatabase();
        $db = $db->table(self::getTableName());
        for ($i = 0; $i < count($keys); $i++) {
            $db = $db->where($keys[$i], '=', $this->data[$keys[$i]]);
        }
        return $db->delete();
    }

    public function save() {
        if (count($this->data_changed) == count($this->data)) {
            $this->insert();
        } else {
            $this->update();
        }
    }

    /**
     * The data of the object
     * @var array
     */
    public $data = array();

    /**
     * The changed data of the object
     * @var array
     */
    public $data_changed = array();

    /**
     * Gets a property field value. If the property is not defined
     * in the data OutOfRangeException will be thrown.
     * @param $name String the name of the property
     * @throws InvalidArgumentException if the given parameter is not string 
     * @throws OutOfRangeException if the parameter is not in the data
     */
    function get($name) {
        if (is_string($name) == false) {
            throw new InvalidArgumentException("The first parameter in the get method in " . get_class($this) . " MUST be of type String: <b>" . gettype($name) . "</b> given");
        }

        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } else {
            throw new OutOfRangeException("There is no $name field in " . get_class($this) . "!");
        }
    }

    /**
     * @param String $name
     * @param String $value
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     */
    public function set($name, $value) {
        if (is_string($name) == false) {
            throw new InvalidArgumentException("The first parameter in the set method class " . get_class($this) . " MUST be of type String: <b>" . gettype($name) . "</b> given");
        }

        if (array_key_exists($name, $this->data)) {
            if ($this->data[$name] != $value) {
                $this->data[$name] = $value;
                $this->data_changed[$name] = $value;
            }
        } else {
            $this->data[$name] = $value;
            $this->data_changed[$name] = $value;
        }
    }
}
