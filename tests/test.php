<?php

define("ENVIRONMENT", 'testing');

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/helper.php';

$tf = new \Testify\Testify("SqlDb Test Suite");

$tf->beforeEach(function ($tf) {
    tableSqlite()->drop();

    tableSqlite()->column('Id', 'INTEGER', 'PRIMARY AUTOINCREMENT')
        ->column('FirstName', 'STRING')
        ->column('MiddleNames', 'STRING')
        ->column('LastName', 'STRING')
        ->column('Birthday', 'DATETIME', 'DEFAULT NULL')
        ->create();
    
    tableMySql()->drop();

    tableMySql()->column('Id', 'INTEGER', 'PRIMARY AUTOINCREMENT')
        ->column('FirstName', 'STRING')
        ->column('MiddleNames', 'STRING')
        ->column('LastName', 'STRING')
        ->column('Birthday', 'DATETIME', 'DEFAULT NULL')
        ->create();
});

$tf->test("Testing Creating Tables", function ($tf) {
    //db()->debug = true;

    $result = dbSqlite()->table('test_creating_tables')
        ->column('Id', 'INTEGER', 'PRIMARY AUTO_INCREMENT')
        ->create();

    $lastestSql = array_pop(dbSqlite()->sqlLog);

    // var_dump($lastestSql);

    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "CREATE TABLE 'test_creating_tables'(Id INTEGER);");
});

$tf->test("Testing Inserting Rows", function ($tf) {
    //db()->debug = true;

    $result = tableSqlite()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);

    $lastestSql = array_pop(dbSqlite()->sqlLog);

    // var_dump($lastestSql);

    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "INSERT INTO 'tests'('FirstName','LastName') VALUES ('John','Doe')");
});

$tf->test("Testing Deleting Rows", function ($tf) {
    //db()->debug = true;

    $id = tableSqlite()->nextId('Id');

    $result = tableSqlite()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);

    $tf->assertTrue($result);

    $result = tableSqlite()->where('Id', '=', $id)->delete();

    $lastestSql = array_pop(dbSqlite()->sqlLog);

    // var_dump($lastestSql);

    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "DELETE FROM 'tests' WHERE Id = '$id';");
});

$tf->test("Testing nextId", function ($tf) {
    //db()->debug = true;

    $id = tableSqlite()->nextId('Id');
    $result = tableSqlite()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 1);

    $id = tableSqlite()->nextId('Id');
    $result = tableSqlite()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 2);

    $id = tableSqlite()->nextId('Id');
    $result = tableSqlite()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 3);
});

$tf->test("Testing lastInsertId", function ($tf) {
    //db()->debug = true;

    $result = tableSqlite()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = tableSqlite()->lastInsertId('Id');
    $tf->assertEquals($id, 1);

    $result = tableSqlite()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = tableSqlite()->lastInsertId('Id');
    $tf->assertEquals($id, 2);

    $result = tableSqlite()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = tableSqlite()->lastInsertId('Id');
    $tf->assertEquals($id, 3);
});

$tf->test("Testing WHERE clauses", function ($tf) {
    //db()->debug = true;

    $result = tableSqlite()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);

    $result = tableSqlite()->insert(['FirstName' => 'Ben', 'LastName' => 'Smith']);
    $tf->assertTrue($result);

    $result = tableSqlite()->insert(['FirstName' => 'Tom', 'LastName' => 'Johnson']);
    $tf->assertTrue($result);

    $result = tableSqlite()->insert(['FirstName' => 'Sean', 'LastName' => 'Farah']);
    $tf->assertTrue($result);

    $result = tableSqlite()->where('FirstName', '=', 'Tom')->select();
    $tf->assertEquals(count($result), 1);

    $result = tableSqlite()->where('FirstName', '=', 'Ben')->where('FirstName', '=', 'Sean', 'OR')->select();
    $tf->assertEquals(count($result), 2);

    $lastestSql = array_pop(dbSqlite()->sqlLog);

    $tf->assertEquals($lastestSql, "SELECT * FROM 'tests' WHERE FirstName = 'Ben' OR FirstName = 'Sean';");
});

$tf();
