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
            'dbname' => 'test',
            'user' => 'root',
            'password' => '',
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
            // 'database_type' => 'mysql',
            // 'database_host' => "localhost",
            // 'database_name' => "test",
            // 'database_user' => "root",
        );
        $this->dbal = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $this->sqldb = dbMySql();
    }
    public function testInit()
    {
        $queryBuilder = $this->dbal->createQueryBuilder();

        $doctrineSql = (string) $queryBuilder->select('id', 'name')->from('users');
        $sqldbSql = $this->sqldb->table('users')->sql()->select(['id', 'name']);
        $sqldbSql = str_replace(["`", ';'], '', $sqldbSql); // Doctrine does not use quotes
        $sqldbSql = str_replace('id,name', 'id, name', $sqldbSql); // Doctrine uses spaces between columnss
        $this->assertEquals($sqldbSql, $doctrineSql);
    }

    public function testWhereValuesEscaped()
    {
        $sqldbSql = $this->sqldb->table('users')->where('id', '=', 'tom\' or 1=1;–-')->sql()->select();
        $this->assertStringContainsStringIgnoringCase("'tom\' or 1=1;–-';", $sqldbSql);

        // $this->sqldb->debug = true;
        $this->sqldb->table('users')->column('id', 'string')->column('name', 'string')->create();
        $this->sqldb->table('users')->insert(['id' => '1', 'name' => 'Tom']);
        $this->sqldb->table('users')->insert(['id' => '2', 'name' => 'Ben']);
        $result = ($this->sqldb->table('users')->executeQuery($sqldbSql));
        //var_dump($result);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        
    }
}
