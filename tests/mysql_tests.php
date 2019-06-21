<?php
dbMysql()->debug = true;

$tf->test("MySQL. Testing Creating Tables", function ($tf) {
    //db()->debug = true;
    $result = dbMysql()->table('test_creating_tables')
        ->column('Id', 'INTEGER', 'PRIMARY AUTO_INCREMENT')
        ->create();
    $lastestSql = array_pop(dbMysql()->sqlLog);
    //var_dump($lastestSql);
    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "CREATE TABLE `test_creating_tables`(`Id` BIGINT PRIMARY KEY AUTO_INCREMENT);");
    
    $result = dbMysql()->table('test_creating_tables')->exists();
    $tf->assertTrue($result);
});

$tf->test("MySQL. Testing Test Table Exists", function ($tf) {
    //db()->debug = true;
    $result = tableMysql()->exists();
    $tf->assertTrue($result);
});

$tf->test("MySQL. Testing Inserting Rows", function ($tf) {
    //db()->debug = true;
    $result = tableMysql()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $lastestSql = array_pop(dbMysql()->sqlLog);
    //var_dump($lastestSql);
    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "INSERT INTO `tests`(`FirstName`,`LastName`) VALUES ('John','Doe')");
});

$tf->test("MySQL. Testing Deleting Rows", function ($tf) {
    //db()->debug = true;
    $id = tableMysql()->nextId('Id');
    $result = tableMysql()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $result = tableMysql()->where('Id', '=', $id)->delete();
    $lastestSql = array_pop(dbMysql()->sqlLog);
    var_dump($lastestSql);
    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "DELETE FROM `tests` WHERE `Id` = '$id';");
});

$tf->test("Sqlite. Testing nextId", function ($tf) {
    //db()->debug = true;
    $id = tableMysql()->nextId('Id');
    $result = tableMysql()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 1);
    $id = tableMysql()->nextId('Id');
    $result = tableMysql()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 2);
    $id = tableMysql()->nextId('Id');
    $result = tableMysql()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 3);
});

$tf->test("MySQL. Testing lastInsertId", function ($tf) {
    //db()->debug = true;
    $result = tableMysql()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = tableSqlite()->lastInsertId('Id');
    $tf->assertEquals($id, 1);
    $result = tableMysql()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = tableMysql()->lastInsertId('Id');
    $tf->assertEquals($id, 2);
    $result = tableMysql()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = tableMysql()->lastInsertId('Id');
    $tf->assertEquals($id, 3);
});

$tf->test("MySQL. Testing WHERE clauses", function ($tf) {
    //db()->debug = true;
    $result = tableMysql()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $result = tableMysql()->insert(['FirstName' => 'Ben', 'LastName' => 'Smith']);
    $tf->assertTrue($result);
    $result = tableMysql()->insert(['FirstName' => 'Tom', 'LastName' => 'Johnson']);
    $tf->assertTrue($result);
    $result = tableMysql()->insert(['FirstName' => 'Sean', 'LastName' => 'Farah']);
    $tf->assertTrue($result);
    $result = tableMysql()->where('FirstName', '=', 'Tom')->select();
    $tf->assertEquals(count($result), 1);
    $result = tableMysql()->where('FirstName', '=', 'Ben')->where('FirstName', '=', 'Sean', 'OR')->select();
    $tf->assertEquals(count($result), 2);
    $lastestSql = array_pop(dbMysql()->sqlLog);
    $tf->assertEquals($lastestSql, "SELECT * FROM 'tests' WHERE FirstName = 'Ben' OR FirstName = 'Sean';");
});
