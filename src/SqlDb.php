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

//============================= START OF CLASS ==============================//
// CLASS: SqlDB                                                              //
//===========================================================================//
/**
 * The SqlDB class provides easy conectivity to different data storage
 * facilities in an object orientated approach through unified methods
 * thus giving the possibility of easy change of environment.
 * <code>
 * // Creating a new MySQL Database
 * $mysqldb = new SqlDB(array(
 *      'database_type'=>'mysql',
 *      'database_name'=>'db_name',
 *      'database_host'=>'db_host',
 *      'database_user'=>'db_user',
 *      'database_pass'=>'db_pass'
 *      ));
 *
 * // Creating a new SQLite Database
 * $sqlitedb = new SqlDB(array(
 *      'database_type'=>'sqlite',
 *      'database_name'=>'db_name',
 *      'database_host'=>'db_host',
 *      'database_user'=>'db_user',
 *      'database_pass'=>'db_pass'
 *      ));
 * </code>
 * @package Simplest
 */
class SqlDb {

    /**
     * @var \PDO
     */
    public $dbh = false;

    /**
     * @var string
     */
    public $database_type = '';

    /**
     * @var string
     */
    public $database_name = '';

    /**
     * @var string
     */
    public $database_host = '';

    /**
     * @var string
     */
    public $database_user = '';

    /**
     * @var string
     */
    public $database_pass = '';

    /**
     * @var string
     */
    public $dsn = '';

    /**
     * @var boolean
     */
    public $debug = false;

    /**
     * @var array
     */
    public $sql = array();
    public $sqlLog = array();

    /**
     * Constructor
     * @param array $options
     */
    function __construct($options = array()) {
        $this->database_type = isset($options['database_type']) == false ? '' : trim($options['database_type']);
        $this->database_name = isset($options['database_name']) == false ? '' : trim($options['database_name']);
        $this->database_host = isset($options['database_host']) == false ? '' : trim($options['database_host']);
        $this->database_user = isset($options['database_user']) == false ? '' : trim($options['database_user']);
        $this->database_pass = isset($options['database_pass']) == false ? '' : trim($options['database_pass']);
        $this->dsn = isset($options['dsn']) == false ? '' : trim($options['dsn']);
    }

    /**
     * @return \PDO
     */
    function getPdo() {
        $this->open();
        return $this->dbh;
    }

    /**
     * Sets the PDO object to be used for the instance
     * @param \PDO $pdo
     */
    function setPdo($pdo) {
        $this->dbh = $pdo;
        $name = $this->dbh->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($name == "mysql") {
            $this->database_type = "mysql";
            $this->database_name = $pdo->query('select database()')->fetchColumn();
        } else if ($name == "sqlite") {
            $this->database_type = "sqlite";
        }
    }

    /**
     * Opens the connection to the database. By default opening the
     * connection is handled automatically, and direct calls to this
     * function are not needed.
     * @return boolean
     */
    function open() {
        if ($this->dbh != false) {
            return true;
        }

        if ($this->database_type == 'sqlite') {
            $database_host = '';
            if ($this->database_host != '') {
                $database_host = str_replace("\\", DIRECTORY_SEPARATOR, str_replace("/", DIRECTORY_SEPARATOR, $this->database_host));
                // Add final backslash
                if (substr($database_host, -1, 1) != DIRECTORY_SEPARATOR) {
                    $database_host = $database_host . DIRECTORY_SEPARATOR;
                }
            }
            $database_path = $database_host . $this->database_name;
            $this->dsn = 'sqlite:' . $database_path;
        }

        if ($this->database_type == 'sqlitedb') {
            $this->dsn = 'sqlite::memory:';
        }

        if ($this->database_type == 'mysql') {
            $this->dsn = 'mysql:dbname=' . $this->database_name . ';host=' . $this->database_host;
        }

        // If no DSN and no auto DSN created
        if ($this->dsn == '') {
            die('DSN not set');
        }

        // Open connection
        try {
            $this->dbh = new \PDO($this->dsn, $this->database_user, $this->database_pass);
            $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (\PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
        return false;
    }

    /**
     * Closes the connection to the database. By default closing the
     * connection is handled automatically, and direct calls to this
     * function are not needed.
     * @return void
     */
    function close() {
        $this->dbh = null;
    }

    /** The column method specifies the desired columns in a table.
     * <code>
     * // Creating table USERS with two columns USER_ID, and USER_NAME
     * $database->table("USERS")
     *     ->column("USER_ID","INTEGER")
     *     ->column("USER_NAME","STRING")
     *     ->create();
     * </code>
     * @param String the name of the column
     * @param String the type of the column (STRING, INTEGER, FLOAT, TEXT, BLOB)
     * @param String the attributes of the column (NOT NULL PRIMARY KEY AUTO_INCREMENT)
     * @return SqlDb an instance of this database
     * @access public
     */
    function column($column_name, $column_type = null, $column_properties = null) {
        if (isset($this->sql["table"]) == false) {
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>column($column,$details)</b>: Trying to attach column to non-specified table!', E_USER_ERROR);
        }

        $current_table = (count($this->sql["table"]) - 1);
        $current_table_name = $this->sql["table"][$current_table];

        if (isset($this->sql["columns"]) == false) {
            $this->sql["columns"] = array();
        }

        if (isset($this->sql["columns"][$current_table_name]) == false) {
            $this->sql["columns"][$current_table_name] = array();
        }

        $this->sql["columns"][$current_table_name][] = array($column_name, $column_type, $column_properties);
        return $this;
    }

    /**
     * Used by the SQLiteDb queries
     * @param string $sql
     * @return string
     */
    function post($sql) {
        $http = new HttpClient($this->database_host);
        $result = $http->post([
            'api_key' => $this->database_pass,
            'sql' => $sql,
        ]);
        if ($http->getResponseStatus() != 200) {
            return ['status' => 'error', 'message' => 'Response not 200, but ' . $http->getResponseStatus()];
        }
        $responseJson = $http->getResponseBody();
        if ($this->debug) {
            $this->debug('SQLiteDb Response: ' . $responseJson);
        }
        $response = json_decode($responseJson, true);
        if ($response == false) {
            return ['status' => 'error', 'message' => 'Response not JSON, but ' . $responseJson];
        }
        return $response;
    }

    /**
     * The executeNonQuery method executes an non-row fetching SQL.
     * <code>
     * // Selecting all the rows from a database
     * $result = $database->executeNonQuery("INSERT INTO TABLE_NAME");
     * </code>
     * @return mixed false if the query fails, SQL specific result otherwise
     * @access public
     */
    function executeNonQuery($sql) {
        $this->open();
        if ($this->debug) {
            $this->debug('START: Executing non query...');
        }
        if ($this->debug) {
            $this->debug(' - Executing SQL:"' . $sql . '"');
        }

        $this->sqlLog[] = $sql;

        if ($this->database_type == 'sqlitedb') {
            try {
                $response = $this->post($sql);
                if ($response['status'] == 'success') {
                    $this->debug('END: Executing non query SUCCESS.');
                    return true;
                }
            } catch (\Exception $e) {
                if ($this->debug) {
                    $this->debug(' - Exception: ' . $e->getMessage());
                }
            }
        } else {
            try {
                $result = $this->dbh->exec($sql);
                if ($this->debug) {
                    $this->debug('END: Executing non query SUCCESS.');
                }
                return $result;
            } catch (\PDOException $e) {
                if ($this->debug) {
                    $this->debug(' - Exception: ' . $e->getMessage());
                }
            }
        }
        if ($this->debug) {
            $this->debug('END: Executing non query FAILED.');
        }
        return false;
    }

    /**
     * The executeQuery method will execute a SQL query and will return
     * the results in a PHP array. Default is associative array.
     *
     * <code>
     * // Selecting all the rows from a database
     * $result = $database->executeQuery("SELECT * FROM TABLE_NAME");
     * </code>
     * @return array|boolean array with the query result, false if the query fails
     * @access public
     */
    function executeQuery($sql, $return_type = \PDO::FETCH_ASSOC) {
        $this->open();
        $result = array();
        if ($this->debug) {
            $this->debug('START: Executing query...');
        }
        if ($this->debug) {
            $this->debug(' - Executing SQL:"' . $sql . '"');
        }

        if ($this->database_type == 'sqlitedb') {
            try {
                $response = $this->post($sql);
                if ($response['status'] == 'success') {
                    $this->debug('END: Executing query SUCCESS.');
                    return $response['data'];
                }
            } catch (\Exception $e) {
                if ($this->debug) {
                    $this->debug(' - Exception: ' . $e->getMessage());
                }
            }
        } else {

            try {
                $this->sqlLog[] = $sql;
                foreach ($this->dbh->query($sql, $return_type) as $row) {
                    $result[] = $row;
                }
                if ($this->debug) {
                    $this->debug('END: Executing query SUCCESS.');
                }
                return $result;
            } catch (\PDOException $e) {
                if ($this->debug) {
                    $this->debug(' - Exception: ' . $e->getMessage());
                }
            }
        }

        if ($this->debug) {
            $this->debug('END: Executing query FAILED.');
        }
        return false;
    }

    /**
     * The create method creates new database or table.
     * If the database or table can not be created it will return false.
     * False will be returned if the database or table already exist.
     * <code>
     * // Creating a new database
     * $database->create();
     *
     * // Creating a new table
     * $database->table("STATES")
     *     ->column("STATE_NAME","STRING")
     *     ->create();
     * </code>
     * @return boolean true, on success, false, otherwise
     * @access public
     */
    function create($table_columns = array()) {
        // START: Creating new table
        if (isset($this->sql["table"])) {
            $table_name = $this->sql["table"][0];
            $table_columns = count($table_columns) > 0 ? $table_columns : (isset($this->sql["columns"][$table_name]) == false ? array() : $this->sql["columns"][$table_name]);
            $this->sql = array(); // Emptying the SQL array
            if ($this->database_type == 'mysql') {
                $sql = "CREATE TABLE `" . $table_name . "`(" . $this->columns_to_sql($table_columns) . ");";
            }
            if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
                $sql = "CREATE TABLE '" . $table_name . "'(" . $this->columns_to_sql($table_columns) . ");";
            }
            $result = $this->executeNonQuery($sql);
            return ($result === false) ? false : true;
        }
        // END: Creating new table
        // START: Creating new database
        else {
            if ($this->debug) {
                $this->debug('START: Creating database "' . $this->database_name . '"...');
            }
            $this->sql = array(); // Emptying the SQL array
            // MySQL
            if ($this->database_type == 'mysql') {
                $sql = 'CREATE DATABASE `' . $this->database_name . '`;';
                $temp_dbname = $this->database_name;
                $this->database_name = '';
                $result = $this->open();
                $this->database_name = $temp_dbname;
                if ($result == false) {
                    if ($this->debug) {
                        $this->debug('END: Connection to database "' . $this->database_name . '" FAILED! Check database connection!');
                    }
                    return false;
                }
                $result = $this->executeNonQuery($sql);
                $this->dsn = ''; // Important
                $this->close(); // Important

                if ($result == true) {
                    if ($this->debug) {
                        $this->debug('END: Creating database <b>"' . $this->database_name . '"</b> SUCCESS!');
                    }
                    return true;
                }
            }

            // SQLite
            if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
                $result = $this->open();
                if ($result == true) {
                    if ($this->debug) {
                        $this->debug('END: Creating database <b>"' . $this->database_name . '"</b> SUCCESS!');
                    }
                    return true;
                }
            }
            if ($this->debug) {
                $this->debug('END: Creating database <b>"' . $this->database_name . '"</b> FAILED! Check host connection!');
            }
            return false;
        }
        // END: Creating new database
    }

    /**
     * The delete method deletes a row in a table. For deleting a database
     * or table use the drop method.
     * <code>
     * // Deleting a row
     * $database->table("STATES")->where("STATE_NAME","=","Alabama")->delete();
     * </code>
     * @return boolean true, on success, false, otherwise
     * @access public
     */
    function delete() {
        if (isset($this->sql["table"]) == false)
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>delete()</b>: Not specified table to delete from!', E_USER_ERROR);
        $table_name = $this->sql["table"][0];
        $where = isset($this->sql["where"]) == false ? '' : $this->where_to_sql($this->sql["where"]);
        $orderby = isset($this->sql["orderby"]) == false ? '' : $this->orderby_to_sql($this->sql["orderby"]);
        $limit = (isset($this->sql["limit"]) == false) ? '' : " LIMIT " . $this->sql["limit"];

        if ($this->database_type == 'mysql') {
            $sql = 'DELETE FROM `' . $table_name . '`' . $where . $orderby . $limit . ';';
        }

        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $sql = "DELETE FROM '" . $table_name . "'" . $where . ";";
        }

        $this->sql = array(); // Emptying the SQL array
        $result = $this->executeNonQuery($sql);
        if ($result === false)
            return false;
        return $result;
    }

    /**
     * The drop method drops/deletes a database or table.
     * <code>
     * // Dropping a database
     * $database->drop();
     *
     * // Dropping a table
     * $database->table("STATES")->drop();
     * </code>
     * @return boolean true, on success, false, otherwise
     * @access public
     */
    function drop() {
        // Drop table SQL query
        if (isset($this->sql["table"])) {
            $table_name = $this->sql["table"][0];
            if ($this->database_type == 'mysql') {
                $sql = 'DROP TABLE `' . $table_name . '`;';
            }
            if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
                $sql = "DROP TABLE '" . $table_name . "';";
            }
            $this->sql = array(); // Emptying the SQL array
            $result = $this->executeNonQuery($sql);
            if ($result === false) {
                return false;
            }
            return true;
        }

        // START: Deleting database
        else {
            $this->sql = array(); // Emptying the SQL array
            if ($this->debug) {
                $this->debug('START: Delete database "' . $this->database_name . '"...');
            }

            if ($this->database_type == 'mysql') {
                $sql = 'DROP DATABASE `' . $this->database_name . '`;';
                $result = $this->executeNonQuery($sql);
                if ($result === false) {
                    if ($this->debug) {
                        $this->debug('END: Database was SUCCESSFULLY deleted.');
                    }
                    return false;
                }
                return true;
            }
            if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
                $result = $this->close();
                $database_host = '';
                if ($this->database_host != '') {
                    $database_host = str_replace("\\", DIRECTORY_SEPARATOR, str_replace("/", DIRECTORY_SEPARATOR, $this->database_host));
                    // Add final backslash
                    if (substr($database_host, -1, 1) != DIRECTORY_SEPARATOR) {
                        $database_host = $database_host . DIRECTORY_SEPARATOR;
                    }
                }
                $database_path = $database_host . $this->database_name;
                if (@unlink($database_path)) {
                    if ($this->debug) {
                        $this->debug('END: Database was SUCCESSFULLY deleted.');
                    }
                    return true;
                } else {
                    if ($this->debug) {
                        $this->debug('END: Database deleting FAILED.');
                    }
                    return false;
                }
            }
        }
        // END: Deleting database
    }

    /**
     * The <b>exists</b> method checks, if a database or a table exists.
     * Keep in mind the check is case insensitive.
     * <code>
     * // Checking, if a database exists
     * if ($database->exists() == false){
     *   echo "Database exists!"
     * } else {
     *   echo "Database does not exist!"
     * }
     *
     * // Checking, if a table exists
     * if ($database->table("STATES")->exists() == false) {
     *   echo "Table exists!"
     * } else {
     *   echo "Table does not exist!"
     * }
     * </code>
     * @return boolean true, on success, false, otherwise
     * @access public
     */
    function exists() {
        // Checking tables
        if (isset($this->sql["table"])) {
            $table_name = $this->sql["table"][0];
            $this->sql = array(); // Emptying the SQL array
            if ($this->database_type == 'mysql') {
                $tables = $this->tables();
                if ($tables == false) {
                    return false;
                }
                $tables = array_map('strtolower', $tables);
                return in_array(strtolower($table_name), $tables);
            }
            if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
                $tables = $this->tables();
                if ($tables == false) {
                    return false;
                }
                $tables = array_map('strtolower', $tables);
                return in_array(strtolower($table_name), $tables);
            }
        }

        // START: Checking database
        else {
            $this->sql = array(); // Emptying the SQL array
            if ($this->debug) {
                $this->debug('START: Checking if database "' . $this->database_name . '" exists...');
            }

            if ($this->database_type == 'mysql') {
                $result = $this->executeQuery('SHOW DATABASES;');
                if ($result === false)
                    return false;
                $databases = array();
                foreach ($result as $row) {
                    $databases[] = $row['Database'];
                }
                $databases = array_map('strtolower', $databases);
                $result = in_array(strtolower($this->database_name), $databases);
                if ($result == true) {
                    $this->debug('END: Database "' . $this->database_name . '" exists');
                } else {
                    $this->debug('END: Database "' . $this->database_name . '" DOES NOT exist');
                }
                return $result;
            }

            if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
                $database_host = '';
                if ($this->database_host != '') {
                    $database_host = str_replace("\\", DIRECTORY_SEPARATOR, str_replace("/", DIRECTORY_SEPARATOR, $this->database_host));
                    // Add final backslash
                    if (substr($database_host, -1, 1) != DIRECTORY_SEPARATOR)
                        $database_host = $database_host . DIRECTORY_SEPARATOR;
                }
                $database_path = $database_host . $this->database_name;
                $result = file_exists($database_path);
                if ($this->debug) {
                    if ($result == true) {
                        $this->debug('END: Database "' . $this->database_name . '" exists');
                    } else {
                        $this->debug('END: Database "' . $this->database_name . '" DOES NOT exist');
                    }
                }
                return $result;
            }
        }
        // END: Check database
    }

    /**
     * Groups by a column name
     * @param SqlDb $column_name
     * @return $this
     */
    function groupBy($column_name) {
        if (isset($this->sql["groupby"]) == false) {
            $this->sql["groupby"] = array();
        }
        $this->sql["groupby"][] = array("COLUMN" => $column_name);
        return $this;
    }

    /**
     * Inserts a row in a table.
     * <code>
     * $user = array("USER_ID"=>3,"USER_MANE"=>"Peter");
     * $database->table("USERS")->insert($user);
     * </code>
     * @param Array an associative array, where keys are the column names of the table
     * @return boolean true, on success, false, otherwise
     * @access public
     */
    function insert($row_values) {
        $this->open();
        if (isset($this->sql["table"]) == false)
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>insert($row_values)</b>: Not specified table to insert a row in!', E_USER_ERROR);
        if (is_array($row_values) == false)
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>insert($row_values)</b>: Parameter <b>$row_values</b> MUST BE of type Array - <b style="color:red">' . gettype($row_values) . '</b> given!', E_USER_ERROR);
        $table_name = $this->sql["table"][0];

        if ($this->database_type == 'mysql') {
            foreach ($row_values as $key => $value) {
                $row_values[$key] = is_null($value) ? 'NULL' : $this->dbh->quote($value);
            }
            $values = implode(",", array_values($row_values));
            $fields = "`" . implode("`" . "," . "`", array_keys($row_values)) . "`";
            $sql = 'INSERT INTO `' . $table_name . '`(' . $fields . ') VALUES (' . $values . ')';
        }

        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            foreach ($row_values as $key => $value) {
                $row_values[$key] = is_null($value) ? 'NULL' : $this->dbh->quote($value);
            }
            $values = implode(",", array_values($row_values));
            $fields = implode("','", array_keys($row_values));
            $sql = "INSERT INTO '" . $table_name . "'('" . $fields . "') VALUES (" . $values . ")";
        }
        $this->sql = array(); // Emptying the SQL array
        $result = $this->executeNonQuery($sql);
        if ($result === false)
            return false;
        return $result;
    }

    /**
     * Joins two tables
     * @param String the name of the table
     * @return SqlDb an instance of this database
     * @access public
     */
    function join($table_name, $column1, $column2, $type = "", $alias = "") {
        if (is_string($table_name) == false) {
            throw new \RuntimeException('In class ' . get_class($this) . ' in method join($table_name,$column1,$column2): $table_name parameter MUST BE of type string');
        }
        if (is_string($column1) == false) {
            throw new \RuntimeException('In class ' . get_class($this) . ' in method join($table_name,$column1,$column2): $column1 parameter MUST BE of type string');
        }
        if (is_string($column2) == false) {
            throw new \RuntimeException('In class ' . get_class($this) . ' in method join($table_name,$column1,$column2): $column2 parameter MUST BE of type string');
        }
        if (isset($this->sql["join"]) == false) {
            $this->sql["join"] = array();
        }
        $this->sql["join"][] = array($table_name, $column1, $column2, $type, $alias);
        return $this;
    }

    /**
     * Returns the last autogenerated ID by this database
     * Note! Not implemented for the Text DB
     *
     * <code>
     * // Creating database with autoincremented field
     * $db->table("USERS")
     *      ->column("ID","BIGINT","PRIMARY KEY AUTOINCREMENT")
     *      ->column("EMAIL","STRING")
     *    ->create();
     *
     * // Inserting new user
     * $db->insert(array("EMAIL"=>"email@server.com"));
     *
     * // Getting the new ID
     * $user_id = $db->last_insert_id();
     * </code>
     * @return mixed rows as associative array, false on error
     * @access public
     */
    function lastInsertId() {
        // MySQL
        if ($this->database_type == 'mysql') {
            $sql = 'SELECT LAST_INSERT_ID();';
        }

        // SQLite
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $sql = 'SELECT LAST_INSERT_ROW_ID();';
            return $this->dbh->lastInsertId();
        }

        $this->sql = array(); // Emptying the SQL array
        $result = $this->executeQuery($sql);
        if ($result === false)
            return false;
        return $result[0]["LAST_INSERT_ID()"];
    }

    /**
     * Specifies the limit of the selection.
     * <code>
     * // Selects the first ten rows from the table
     * $database->table("USERS")->limit(0, 10)->select();
     * </code>
     * @param int the start of the selection
     * @param int the end of the selection
     * @return SqlDB an instance of this database
     * @access public
     */
    function limit($start, $end = null) {
        if (isset($this->sql["limit"]) == false)
            $this->sql["limit"] = array();
        if (is_null($end)) {
            $this->sql["limit"] = $start;
        } else {
            $this->sql["limit"] = $start . "," . $end;
        }
        return $this;
    }

    /**
     * Returns the last autogenerated ID by this database
     * Note! Not implemented for the Text DB
     *
     * <code>
     * // Creating database with autoincremented field
     * $id = $db->table("USERS")->column("ID")->next_id();
     *
     * // Inserting new user
     * $db->insert(array("ID"=>$id,"EMAIL"=>"email@server.com"));

     * </code>
     * @return Integer next ID in a column
     * @access public
     */
    function nextId($column_name) {
        if (isset($this->sql["table"]) == false) {
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>select()</b>: Not specified table to select from!', E_USER_ERROR);
        }

        $table_name = $this->sql["table"][0];

        // MySQL
        if ($this->database_type == 'mysql') {
            $sql = 'SELECT `' . $column_name . '` FROM `' . $table_name . '` ORDER BY `' . $column_name . '` DESC LIMIT 1';
        }

        // SQLite
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $sql = "SELECT " . $column_name . " FROM '" . $table_name . "' ORDER BY " . $column_name . " DESC LIMIT 1";
        }

        $this->sql = array(); // Emptying the SQL array

        $result = $this->executeQuery($sql);

        if ($result === false) {
            return false;
        }

        $next_id = false;
        if (count($result) < 1) {
            $next_id = 1;
        } else {
            $last_id = $result[0][$column_name];
            $next_id = $last_id + 1;
        }
        return $next_id;
    }

    /** Counts the number of rows, based on criteria.
     * <code>
     * // Returns the number of all the rows from the table
     * $rows = $db->table("USERS")->num_rows();
     *
     * // Returns number of the rows where the column NAME is different from Peter, in descending order
     * rows = $db->table("USERS")->where("NAME","!=","Peter")->orderby("NAME","desc")->num_rows();
     * </code>
     * @return Integer the number of rows
     * @access public
     */
    function numRows() {
        if (isset($this->sql["table"]) == false) {
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>select()</b>: Not specified table to select from!', E_USER_ERROR);
        }

        $table_name = $this->sql["table"][0];
        $where = isset($this->sql["where"]) == false ? '' : $this->where_to_sql($this->sql["where"]);
        $orderby = isset($this->sql["orderby"]) == false ? '' : $this->orderby_to_sql($this->sql["orderby"]);
        $limit = (isset($this->sql["limit"]) == false) ? '' : " LIMIT " . $this->sql["limit"];
        $join = isset($this->sql["join"]) == false ? '' : $this->join_to_sql($this->sql["join"], $table_name);

        // MySQL
        if ($this->database_type == 'mysql') {
            $sql = 'SELECT COUNT(*) FROM `' . $table_name . '`' . $join . $where . $orderby . $limit . ';';
        }

        // SQLite and SQLiteDb
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $sql = "SELECT COUNT(*) FROM '" . $table_name . "'" . $join . $where . $orderby . $limit . ";";
        }

        $this->sql = array(); // Emptying the SQL array
        $result = $this->executeQuery($sql);
        if ($result === false)
            return false;
        return $result[0]['COUNT(*)'];
    }

    /**
     * The <b>orderby</b> method specifies in what order (ascending or descending)
     * a selection is to be made.
     * <code>
     * // Selects the row from USERS in alphabetical order based on column "USER_NAME"
     * $db->table("USERS")->orderby("USER_NAME","asc")->select();
     * </code>
     * @param String the name of the column
     * @param String the type of order (asc,desc)
     * @return SqlDB
     * @access public
     */
    function orderBy($column_name, $type = "asc") {
        if (isset($this->sql["orderby"]) == false) {
            $this->sql["orderby"] = array();
        }
        $this->sql["orderby"][] = array("COLUMN" => $column_name, "ORDER_TYPE" => $type);
        return $this;
    }

    function quote($string) {
        $this->open(); // As may not be opened
        return $this->dbh->quote($string);
    }

    /** The <b>select</b> method selects rows from a table, based on criteria.
     * <code>
     * // Selects all the rows from the table
     * $db->table("USERS")->select();
     *
     * // Selects the rows where the column NAME is different from Peter, in descending order
     * $db->table("USERS")
     *     ->where("NAME","!=","Peter")
     *     ->orderby("NAME","desc")
     *     ->select();
     * </code>
     * @return mixed rows as associative array, false on error
     * @access public
     */
    function select($columns = "*") {
        $sql = '';

        if (isset($this->sql["table"]) == false) {
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>select()</b>: Not specified table to select from!', E_USER_ERROR);
        }

        $table_name = $this->sql["table"][0];
        $where = isset($this->sql["where"]) == false ? '' : $this->where_to_sql($this->sql["where"]);
        $orderby = isset($this->sql["orderby"]) == false ? '' : $this->orderby_to_sql($this->sql["orderby"]);
        $limit = (isset($this->sql["limit"]) == false) ? '' : " LIMIT " . $this->sql["limit"];
        $groupby = isset($this->sql["groupby"]) == false ? '' : $this->groupby_to_sql($this->sql["groupby"]);
        $join = isset($this->sql["join"]) == false ? '' : $this->join_to_sql($this->sql["join"], $table_name);
        if (is_array($columns)) {
            if (count($columns) > 0) {
                $columns = implode(',', $columns);
            }
        }

        if ($this->database_type == 'mysql') {
            $sql = 'SELECT ' . $columns . ' FROM `' . $table_name . '`' . $join . $where . $groupby . $orderby . $limit . ';';
        }

        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $sql = "SELECT " . $columns . " FROM '" . $table_name . "'" . $join . $where . $groupby . $orderby . $limit . ";";
        }

        $this->sql = array(); // Emptying the SQL array
        $result = $this->executeQuery($sql);
        if ($result === false) {
            return false;
        }
        return $result;
    }

    /**
     * Selects the first row from the database query
     * @param string $columns
     * @return array|boolean array with the query result, false if the query fails
     */
    function selectOne($columns = '*') {
        $results = $this->limit(1)->select($columns);
        if ($results !== false && count($results) > 0) {
            return $results[0];
        }
        return null;
    }

    /**
     * Selects a single column and returns as array
     * @param string $columnName
     * @return array
     */
    function selectColumn($columnName) {
        $results = $this->select([$columnName]);
        return array_column($results, $columnName);
    }

    /**
     * The <b>table</b> method specifies the table, to which an
     * operation is to be done.
     * <code>
     * // Selects all from table "STATES"
     * $db->table("STATES")->select();
     * </code>
     * @param String the name of the table
     * @return SqlDB an instance of this database
     * @access public
     */
    function table($table) {
        isset($this->sql["table"]) ? $this->sql["table"] = array() : true;
        $this->sql["table"][] = $table;
        return $this;
    }

    /**
     * The columns method returns the columns in a table.
     * <code>
     * $table_columns = $database->table('USERS')->columns();
     * </code>
     * @return array with columns in the table
     * @access public
     */
    function columns($unisex = true) {
        if (isset($this->sql["table"]) == false) {
            throw new \RuntimeException('ERROR: In class <b>' . get_class($this) . '</b> in method <b>columns()</b>: Trying fetch columns from non-specified table!');
        }

        $current_table = (count($this->sql["table"]) - 1);
        $table_name = $this->sql["table"][$current_table];
        $this->sql = array(); // Emptying the SQL array
        $table_columns = array();
        if ($this->database_type == 'mysql') {
            $sql = 'DESCRIBE `' . $table_name . '`';
            $result = $this->executeQuery($sql);
            foreach ($result as $row) {
                $column = array();
                // Name
                $column[0] = $row['Field'];
                // Type
                $column[1] = $row['Type'];
                if ($unisex == true) {
                    if (stripos($row['Type'], 'int') !== false) {
                        $column[1] = 'INTEGER';
                    } else if (stripos($row['Type'], 'char') !== false) {
                        $column[1] = 'STRING';
                    } else if (stripos($row['Type'], 'text') !== false) {
                        $column[1] = 'TEXT';
                    } else if (stripos($row['Type'], 'float') !== false) {
                        $column[1] = 'FLOAT';
                    } else if (stripos($row['Type'], 'blob') !== false) {
                        $column[1] = 'BLOB';
                    }
                }

                // Properties
                $column[2] = '';
                if (stripos($row['Null'], 'no') !== false) {
                    $column[2] .= ' NOT NULL';
                }
                if (stripos($row['Key'], 'pri') !== false) {
                    $column[2] .= ' PRIMARY KEY';
                } else if (stripos($row['Key'], 'uni') !== false) {
                    if (stripos($row['Extra'], 'auto') !== false) {
                        // SQL does not support UNIQUE at the moment
                        // so we replace it with Primary Key :(
                        $column[2] .= ' PRIMARY KEY';
                    } else {
                        $column[2] .= ' UNIQUE';
                    }
                }
                if (stripos($row['Extra'], 'auto') !== false) {
                    $column[2] .= ' AUTO_INCREMENT';
                }
                $table_columns[] = $column;
            }
        }
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $sql = "SELECT * FROM 'SQLITE_MASTER' WHERE type='table' ORDER BY NAME ASC";
            $sql = "PRAGMA table_info('$table_name')";
            $result = $this->executeQuery($sql);
            //var_dump($result);
            //echo '<hr>';
            foreach ($result as $row) {
                $column = array();
                // Name

                $column[0] = $row['name'];

                // Type
                $column[1] = $row['type'];
                if ($unisex == true) {
                    if (stripos($row['type'], 'int') !== false) {
                        $column[1] = 'INTEGER';
                    } else if (stripos($row['type'], 'char') !== false) {
                        $column[1] = 'STRING';
                    } else if (stripos($row['type'], 'text') !== false) {
                        $column[1] = 'TEXT';
                    } else if (stripos($row['type'], 'real') !== false) {
                        $column[1] = 'FLOAT';
                    } else if (stripos($row['type'], 'blob') !== false) {
                        $column[1] = 'BLOB';
                    }
                }

                // Properties
                $column[2] = '';
                if ($row['notnull'] == 99) {
                    $column[2] .= ' NOT NULL';
                }
                if ($row['pk'] == 1) {
                    $column[2] .= ' PRIMARY KEY';
                }
                if ($column['TYPE'] == 'INTEGER' && $row['pk'] == 1) {
                    $column[2] .= ' AUTOINCREMENT';
                }
                $column[2] = trim($column['PROPERTIES']);

                // Default value
                //$column['DEFAULT'] = $row['dflt_value'];
                // Primary key
                //$column['PRIMARY_KEY'] = ($row['pk']==1)?'yes':'no';

                $table_columns[] = $column;
            }
        }
        return $table_columns;
    }

    /**
     * The <b>tables</b> method returns the names of all the tables, that
     * exist in the database.
     * <code>
     * foreach($database->tables() as $table){
     *     echo $table;
     * }
     * </code>
     * @param String the name of the table
     * @return array the names of the tables
     * @access public
     */
    function tables() {
        $tables = array();
        if ($this->database_type == 'mysql') {
            //$sql = "SHOW TABLES";
            $sql = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='" . $this->database_name . "'";
            $result = $this->executeQuery($sql);
            if ($result === false)
                return false;
            foreach ($result as $row) {
                $tables[] = $row['TABLE_NAME'];
            }
            return $tables;
        }

        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $sql = "SELECT * FROM 'SQLITE_MASTER' WHERE type='table' ORDER BY NAME ASC";
            $result = $this->executeQuery($sql);
            if ($result === false) {
                return false;
            }
            foreach ($result as $row) {
                $tables[] = $row['name'];
            }
            return $tables;
        }
        return false;
    }

    /**
     * Starts a transaction
     * @return bool
     */
    function transactionBegin() {
        $this->open();
        return $this->dbh->beginTransaction();
    }

    /**
     * Commits a transaction
     * @return bool
     */
    function transactionCommit() {
        $this->open();
        return $this->dbh->commit();
    }

    /**
     * Rolls back a transaction
     * @return string
     */
    function transactionRollBack() {
        $this->open();
        return $this->dbh->rollBack();
    }

    /**
     * Returns a unique date driven numeric id with default of 20 numbers
     * This is a helper function to simplify generating unique identifiers.
     * <code>
     * $uid = SqlDB::uid(20);
     * $uid = SqlDB::uid(32);
     * </code>
     * @return  string
     */
    public static function uid($length = 20) {
        $uid = date('YmdHis') . substr(explode(" ", microtime())[0], 2, 8) . rand(100000000000, 999999999999);
        return substr($uid, 0, $length);
    }

    /**
     * Returns a pseudo-random Version 4 UUID
     * This is a helper function to simplify generating unique identifiers.
     * <code>
     * // Turn to lowercase and remove dashes to save space
     * $uuid = strtolower(str_replace('-','',SqlDB::uuid()));
     * </code>
     * @return  string
     */
    public static function uuid($options) {
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                // 32 bits for "time_low"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0x3fff) | 0x8000,
                // 48 bits for "node"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        return $uuid;
    }

    /**
     * The <b>update</b> method updates the values of a row in a table.
     * <code>
     * $updated_user = array("USER_MANE"=>"Mike");
     * $database->table("USERS")->where("USER_NAME","==","Peter")->update($updated_user);
     * </code>
     * @param Array an associative array, where keys are the column names of the table
     * @return int 0 or 1, on success, false, otherwise
     * @access public
     */
    function update($row_values) {
        $this->open();
        if (isset($this->sql["table"]) == false) {
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>insert($row_values)</b>: Not specified table to update a row in!', E_USER_ERROR);
        }
        if (is_array($row_values) == false) {
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>insert($row_values)</b>: Parameter <b>$row_values</b> MUST BE of type Array - <b style="color:red">' . gettype($row_values) . '</b> given!', E_USER_ERROR);
        }
        $table_name = $this->sql["table"][0];

        if (isset($this->sql["table"]) == false) {
            trigger_error('ERROR: In class <b>' . get_class($this) . '</b> in method <b>select()</b>: Not specified table to select from!', E_USER_ERROR);
        }
        $table_name = $this->sql["table"][0];
        $where = isset($this->sql["where"]) == false ? '' : $this->where_to_sql($this->sql["where"]);
        $orderby = isset($this->sql["orderby"]) == false ? '' : $this->orderby_to_sql($this->sql["orderby"]);
        $limit = (isset($this->sql["limit"]) == false) ? '' : " LIMIT " . $this->sql["limit"];
        //$join = isset($this->sql["join"]) == false ? '' : $this->join_to_sql($this->sql["join"], $table_name);

        foreach ($row_values as $key => $value) {
            $row_values[$key] = $this->dbh->quote($value);
        }

        if ($this->database_type == 'mysql') {
            $updatesql = array();
            foreach ($row_values as $column => $value) {
                $updatesql[] = "`" . $column . "`=" . $value . "";
            }

            $updatesql = implode(",", $updatesql);
            $sql = 'UPDATE `' . $table_name . '` SET ' . $updatesql . $where . $orderby . $limit . ';';
        }

        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $updatesql = array();
            foreach ($row_values as $column => $value) {
                $updatesql[] = "'" . $column . "'=" . $value . "";
            }

            $updatesql = implode(",", $updatesql);
            $sql = "UPDATE '" . $table_name . "' SET " . $updatesql . $where . $orderby . $limit . ";";
        }

        $this->sql = array(); // Emptying the SQL array
        $result = $this->executeNonQuery($sql);
        if ($result === false) {
            return false;
        }
        return $result;
    }

    /** The <b>where</b> method specifies, which rows to be selected
     * based on a comparison.
     * <code>
     * // Selects the row where USER_NAME equals "Peter"
     * $db->table("USERS")->where("USER_NAME","==","Peter")->select();
     * </code>
     * @param String the name of the column
     * @param String the comparison operator ("!=" or "<>", "=","==" or "===", "<", ">", etc.)
     * @param String the value to be compared with
     * @param String the type of comparison (AND-default, OR)
     * @return SqlDb
     * @access public
     */
    function where($column_name, $comparison_operator = null, $value = null, $type = "AND") {
        if ($comparison_operator !== null and is_string($comparison_operator) == false) {
            throw new \InvalidArgumentException("The second parameter in where method in " . get_class($this) . " MUST be of type String: " . gettype($comparison_operator) . " given");
        }
        if ($value !== null and ( is_string($value) == false and is_numeric($value) == false)) {
            throw new \InvalidArgumentException("The third parameter in where method in " . get_class($this) . " MUST be of type String: " . gettype($value) . " given");
        }
        if (isset($this->sql["where"]) == false) {
            $this->sql["where"] = array();
        }
        if (is_string($column_name)) {
            $this->sql["where"][] = array("COLUMN" => $column_name, "OPERATOR" => $comparison_operator, "VALUE" => $value, "TYPE" => $type);
        }
        if (is_array($column_name)) {
            $array = [];
            foreach ($column_name as $entry) {
                $entryColumnName = isset($entry[0]) ? $entry[0] : '';
                $entryComparisonOperator = isset($entry[1]) ? $entry[1] : null;
                $entryValue = isset($entry[2]) ? $entry[2] : null;
                $entryType = isset($entry[3]) ? $entry[3] : 'AND';
                $array[] = array("COLUMN" => $entryColumnName, "OPERATOR" => $entryComparisonOperator, "VALUE" => $entryValue, "TYPE" => $entryType);
            }
            $this->sql["where"][] = array("WHERE" => $array, "TYPE" => $type);
        }
        return $this;
    }

    /**
     * Raw SQL
     * @param string $sqlWhere
     * @return $this
     */
    function whereRaw($sqlWhere) {
        $this->sql["where"][] = $sqlWhere;
        return $this;
    }

    /**
     * Converts the columns statements to SQL.
     * @return String the colummns SQL string
     * @access private
     */
    private function columns_to_sql($columns) {
        $sql = '';
        // MySQL
        if ($this->database_type == 'mysql') {
            $sql_columns = array();
            foreach ($columns as $column) {
                //var_dump($column);
                $column_name = $column[0];
                $column_type = $column[1];
                $column_properties = isset($column[2]) ? $column[2] : '';

                $sql_column = "`" . $column_name . "`";
                if (strtolower(trim($column_type)) == "integer") {
                    $sql_column .= " BIGINT";
                } else if (strtolower(trim($column_type)) == "string") {
                    $sql_column .= " VARCHAR(255)";
                } else if (strtolower(trim($column_type)) == "float") {
                    $sql_column .= " DOUBLE";
                } else if (strtolower(trim($column_type)) == "text") {
                    $sql_column .= " LONGTEXT";
                } else if (strtolower(trim($column_type)) == "blob") {
                    $sql_column .= " LONGBLOB";
                } else {
                    $sql_column .= " " . $column_type;
                }
                if ($column_properties != '') {
                    $column_properties = str_ireplace('AUTOINCREMENT', 'AUTO_INCREMENT', $column_properties);
                    $sql_column .= " " . $column_properties;
                }
                // 				$sql_column = "`".$column['column_name']."`";
                // 				if(strtolower(trim($column['column_type']))=="integer"){ $sql_column .= " BIGINT"; }
                // 				else if(strtolower(trim($column['column_type']))=="string"){ $sql_column .= " VARCHAR(255)"; }
                // 				else if(strtolower(trim($column['column_type']))=="float"){ $sql_column .= " DOUBLE"; }
                // 				else if(strtolower(trim($column['column_type']))=="text"){ $sql_column .= " LONGTEXT"; }
                // 				else if(strtolower(trim($column['column_type']))=="blob"){ $sql_column .= " LONGBLOB"; }
                // 				else { $sql_column .= " ". $column['column_type']; }
                // 				if($column['column_properties']!=null){ $sql_column .= " ".$column['column_properties']; }
                $sql_columns[] = $sql_column;
            }
            $sql = implode(",", $sql_columns);
        }

        // SQLite
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            $sql_columns = array();
            foreach ($columns as $column) {
                $column_name = $column[0];
                $column_type = $column[1];
                $column_properties = isset($column[2]) ? $column[2] : '';

                if (strtolower(trim($column_type)) == "integer") {
                    $column_type = "INTEGER";
                } else if (strtolower(trim($column_type)) == "string") {
                    $column_type = "TEXT";
                } else if (strtolower(trim($column_type)) == "float") {
                    $column_type = " REAL";
                } else if (strtolower(trim($column_type)) == "text") {
                    $column_type = " TEXT";
                } else if (strtolower(trim($column_type)) == "blob") {
                    $column_type = " BLOB";
                } else if (strtolower(trim($column_type)) == "date") {
                    $column_type = " TEXT";
                } else if (strtolower(trim($column_type)) == "datetime") {
                    $column_type = " TEXT";
                }

                $sql_column = $column_name . " " . $column_type;
                if (isset($column['column_properties'])) {
                    $column_properties = $column_properties;
                    // No AUTOINCREMENT field in SQLite
                    $column_properties = str_ireplace('AUTO_INCREMENT', '', $column_properties);
                    $column_properties = str_ireplace('AUTOINCREMENT', '', $column_properties);
                    $sql_column .= " " . $column_properties;
                }
                $sql_columns[] = $sql_column;
            }
            $sql = implode(",", $sql_columns);
        }
        return $sql;
    }

    /**
     * Joins tables to SQL.
     * @return String the join SQL string
     * @access private
     */
    private function join_to_sql($join, $table_name) {
        $sql = '';
        // MySQL
        if ($this->database_type == 'mysql') {
            foreach ($join as $what) {
                $type = $what[3] ?? '';
                $alias = $what[4] ?? '';
                $sql .= ' ' . $type . ' JOIN `' . $what[0] . '`';
                if ($alias != "") {
                    $sql .= ' AS ' . $alias . '';
                    $what[0] = $alias;
                }
                if ($what[1] == $what[2]) {
                    $sql .= ' USING (`' . $what[1] . '`)';
                } else {
                    $sql .= ' ON ' . $table_name . '.' . $what[1] . '=' . $what[0] . '.' . $what[2];
                }
            }
        }
        // SQLite
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            foreach ($join as $what) {
                $type = $what[3] ?? '';
                $alias = $what[4] ?? '';
                $sql .= " $type JOIN '" . $what[0] . "'";
                if ($alias != "") {
                    $sql .= " AS '$alias'";
                    $what[0] = $alias;
                }
                $sql .= ' ON ' . $table_name . '.' . $what[1] . '=' . $what[0] . '.' . $what[2];
            }
        }

        return $sql;
    }
    
    private function whereToSqlSingle($column, $operator, $value) {
        if ($this->database_type == 'mysql') {
            $column = explode('.', $column);
            $columnQuoted = "`" . implode("`.`", $column) . "`";
            if ($operator == "==" OR $operator == "===") {
                $operator = "=";
            }
            if ($operator == "!=" || $operator == "!==") {
                $operator = "<>";
            }
            if ($value == NULL AND $operator == "=") {
                $sql = $columnQuoted . " IS NULL";
            } elseif ($value == NULL AND $operator == "<>") {
                $sql = $columnQuoted . " IS NOT NULL";
            } else {
                $sql = $columnQuoted . " " . $operator . " '" . $value . "'";
            }
        }
        return $sql;
    }

    /**
     * Converts wheres to SQL
     * @param array $wheres
     * @return string
     */
    private function where_to_sql($wheres) {
        $sql = array();
        // MySQL
        if ($this->database_type == 'mysql') {
            for ($i = 0; $i < count($wheres); $i++) {
                $where = $wheres[$i];
                // Is it a raw where query?
                if (is_string($where)) {
                    $sql[] = $where;
                    continue;
                }
                // Normal where
                if (isset($where['COLUMN']) == true) {
                    $sqlSingle = $this->whereToSqlSingle($where['COLUMN'], $where['OPERATOR'], $where['VALUE']);
                    if ($i == 0) {
                        $sql[] = $sqlSingle;
                    } else {
                        $sql[] = $where['TYPE'] . ' ' . $sqlSingle;
                    }
                } else {
                    $_sql = array();
                    $all = $where['WHERE'];
                    for ($k = 0; $k < count($all); $k++) {
                        $w = $all[$k];
                        $sqlSingle = $this->whereToSqlSingle($w['COLUMN'], $w['OPERATOR'], $w['VALUE']);
                        if ($k == 0) {
                            $_sql[] = $sqlSingle;
                        } else {
                            $_sql[] = $w['TYPE'] . " " . $sqlSingle;
                        }
                    }
                    $_sql = (count($_sql) > 0) ? " (" . implode(" ", $_sql) . ")" : "";

                    if ($i == 0) {
                        $sql[] = $_sql;
                    } else {
                        $sql[] = $where['TYPE'] . " " . $_sql;
                    }
                }
            }
            return (count($sql) > 0) ? " WHERE " . implode(" ", $sql) : "";
        }
        // SQLite
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            for ($i = 0; $i < count($wheres); $i++) {
                $where = $wheres[$i];
                // Is it a raw where query?
                if (is_string($where)) {
                    $sql[] = $where;
                    continue;
                }
                // Normal where
                if (isset($where['COLUMN']) == true) {
                    if ($where['OPERATOR'] == "==" || $where['OPERATOR'] == "===") {
                        $where['OPERATOR'] = "=";
                    }
                    if ($where['OPERATOR'] == "!=") {
                        $where['OPERATOR'] = "<>";
                    }
                    //$sql[] = $where['COLUMN']." ".$where['OPERATOR']." '".$where['VALUE']."'";
                    if ($i == 0) {
                        $sql[] = "" . $where['COLUMN'] . " " . $where['OPERATOR'] . " '" . $where['VALUE'] . "'";
                    } else {
                        $sql[] = $where['TYPE'] . " " . $where['COLUMN'] . " " . $where['OPERATOR'] . " '" . $where['VALUE'] . "'";
                    }
                } else {
                    $_sql = array();
                    $all = $where['WHERE'];
                    for ($k = 0; $k < count($all); $k++) {
                        $w = $all[$k];
                        if ($w['OPERATOR'] == "==" || $w['OPERATOR'] == "===") {
                            $w['OPERATOR'] = "=";
                        }
                        if ($w['OPERATOR'] == "!=" || $w['OPERATOR'] == "!==") {
                            $w['OPERATOR'] = "<>";
                        }
                        if ($k == 0) {
                            $_sql[] = "" . $w['COLUMN'] . " " . $w['OPERATOR'] . " '" . $w['VALUE'] . "'";
                        } else {
                            $_sql[] = $w['TYPE'] . " " . $w['COLUMN'] . " " . $w['OPERATOR'] . " '" . $w['VALUE'] . "'";
                        }
                    }
                    $_sql = (count($_sql) > 0) ? " (" . implode(" ", $_sql) . ")" : "";

                    if ($i == 0) {
                        $sql[] = $_sql;
                    } else {
                        $sql[] = $where['TYPE'] . " " . $_sql;
                    }
                }
            }
            
            //return (count($sql)>0)? " WHERE ".implode(" AND ",$sql):"";
            return (count($sql) > 0) ? " WHERE " . implode(" ", $sql) : "";
        }
    }

    private function groupby_to_sql($groupbys) {
        $sql = array();
        // MySQL
        if ($this->database_type == 'mysql') {
            foreach ($groupbys as $groupby) {
                $sql[] = "`" . $groupby['COLUMN'] . "`";
            }
            return (count($sql) > 0) ? " GROUP BY " . implode(", ", $sql) : "";
        }
        // SQLite
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            foreach ($groupbys as $groupby) {
                $sql[] = "" . $groupby['COLUMN'];
            }
            return (count($sql) > 0) ? " GROUP BY " . implode(", ", $sql) : "";
        }
    }

    private function orderby_to_sql($orderbys) {
        $sql = array();
        // MySQL
        if ($this->database_type == 'mysql') {
            foreach ($orderbys as $orderby) {
                if (strtolower($orderby['ORDER_TYPE']) == "desc" || strtolower($orderby['ORDER_TYPE']) == "descendng") {
                    $orderby['ORDER_TYPE'] = " DESC";
                } else {
                    $orderby['ORDER_TYPE'] = " ASC";
                }
                $sql[] = "`" . $orderby['COLUMN'] . "` " . $orderby['ORDER_TYPE'];
            }
            return (count($sql) > 0) ? " ORDER BY " . implode(", ", $sql) : "";
        }
        // SQLite
        if ($this->database_type == 'sqlite' OR $this->database_type == 'sqlitedb') {
            foreach ($orderbys as $orderby) {
                if (strtolower($orderby['ORDER_TYPE']) == "desc" || strtolower($orderby['ORDER_TYPE']) == "descendng") {
                    $orderby['ORDER_TYPE'] = " DESC";
                } else {
                    $orderby['ORDER_TYPE'] = " ASC";
                }
                $sql[] = "" . $orderby['COLUMN'] . " " . $orderby['ORDER_TYPE'];
            }
            return (count($sql) > 0) ? " ORDER BY " . implode(", ", $sql) : "";
        }
    }

    /**
     * Prints the debug message to the screen or to a supplied file path. If
     * the file doesn't exist it is created.
     * @param String the message to be printed
     * @return void
     */
    protected function debug($msg) {
        $isCli = false;
        if (defined('STDIN')) {
            $isCli = true;
        }

        if (empty($_SERVER['REMOTE_ADDR']) and ! isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
            $isCli = true;
        }
        if ($this->debug) {
            if ($isCli) {
                echo "DEBUG: " . date('Ymd H:i:s') . " - " . $msg . "\n";
            } else {
                echo "<span style='font-weight:bold;color:red;'>DEBUG:</span> " . $msg . "<br />";
            }
        }
    }

}
