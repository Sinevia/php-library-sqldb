<?php

/**
 * Returns a database instance
 * @return \Sinevia\SqlDb
 */
function dbSqlite()
{
    static $db = null;
    if (is_null($db)) {
        $db = new \Sinevia\SqlDb(array(
            'database_type' => 'sqlite',
            'database_host' => ":memory:",
            'database_name' => ":memory:",
            'database_user' => "root",
            'database_pass' => "",
        ));
    }
    return $db;
}

/**
 * Returns a database instance
 * @return \Sinevia\SqlDb
 */
function dbMySql()
{
    static $db = null;
    if (is_null($db)) {
        $db = new \Sinevia\SqlDb(array(
            'database_type' => 'mysql',
            'database_host' => "localhost",
            'database_name' => "test",
            'database_user' => "root",
            'database_pass' => "",
        ));
        if($db->exists()==false){
            $db->create();
        }
    }
    return $db;
}

/**
 * Returns a table instance
 * @return \Sinevia\SqlDb
 */
function tableSqlite()
{
    return dbSqlite()->table('tests');
}

/**
 * Returns a table instance
 * @return \Sinevia\SqlDb
 */
function tableMySql()
{
    return dbMySql()->table('tests');
}
