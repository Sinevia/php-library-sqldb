<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/helper.php';

class SqliteTest extends \PHPUnit\Framework\TestCase
{
    private $dbal = null;
    private $sqldb = null;

    public function setUp(): void
    {
        parent::setUp();
        //..
        $connectionParams = array(
            'dbname' => 'db.sqlite',
            'user' => 'user',
            'password' => 'secret',
            'host' => 'localhost',
            'driver' => 'pdo_sqlite',
        );
        $this->dbal = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $this->sqldb = dbSqlite();
    }
    public function testInit()
    {
        $queryBuilder = $this->dbal->createQueryBuilder();

        $doctrineSql = (string) $queryBuilder->select('i"d', 'name')->from('users');
        $sqldbSql = $this->sqldb->table('users')->sql()->select(['id', 'name']);
        $sqldbSql = str_replace(["'", ';'], '', $sqldbSql); // Doctrine does not use quotes
        $sqldbSql = str_replace('id,name', 'id, name', $sqldbSql); // Doctrine uses spaces between columnss
        $this->assertEquals($sqldbSql, $doctrineSql);
    }
}
