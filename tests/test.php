<?php
define("ENVIRONMENT", 'testing');
require dirname(__DIR__) . '/vendor/autoload.php';
/**
 * Returns a database instance
 * @return \Sinevia\SqlDb
 */
function db()
{
    static $db = null;
    if (is_null($db)) {
        $db = new \Sinevia\SqlDb(array(
            'database_type' => 'sqlite',
            'database_host' => ":memory:",
            'database_name' => ":memory:",
            'database_user' => "test",
            'database_pass' => "",
        ));
    }
    return $db;
}

function table()
{
    return db()->table('tests');
}

//\App\Models\Content\Node::getDatabase()->debug = true;
$tf = new \Testify\Testify("SqlDb Test Suite");

$tf->beforeEach(function ($tf) {
    table()->drop();

    table()->column('Id', 'INTEGER', 'PRIMARY AUTOINCREMENT')
        ->column('FirstName', 'STRING')
        ->column('MiddleNames', 'STRING')
        ->column('LastName', 'STRING')
        ->column('Birthday', 'DATETIME', 'DEFAULT NULL')
        ->create();
});

$tf->test("Testing Creating Tables", function ($tf) {
    //db()->debug = true;

    $result = db()->table('test_creating_tables')
        ->column('Id', 'INTEGER', 'PRIMARY AUTO_INCREMENT')
        ->create();

    $lastestSql = array_pop(db()->sqlLog);

    // var_dump($lastestSql);

    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "CREATE TABLE 'test_creating_tables'(Id INTEGER);");
});

$tf->test("Testing Inserting Rows", function ($tf) {
    //db()->debug = true;

    $result = table()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);

    $lastestSql = array_pop(db()->sqlLog);

    // var_dump($lastestSql);

    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "INSERT INTO 'tests'('FirstName','LastName') VALUES ('John','Doe')");
});

$tf->test("Testing Deleting Rows", function ($tf) {
    //db()->debug = true;

    $id = table()->nextId('Id');

    $result = table()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);

    $tf->assertTrue($result);

    $result = table()->where('Id', '=', $id)->delete();

    $lastestSql = array_pop(db()->sqlLog);

    // var_dump($lastestSql);

    $tf->assertTrue($result);
    $tf->assertEquals($lastestSql, "DELETE FROM 'tests' WHERE Id = '$id';");
});

$tf->test("Testing nextId", function ($tf) {
    //db()->debug = true;

    $id = table()->nextId('Id');
    $result = table()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 1);

    $id = table()->nextId('Id');
    $result = table()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 2);

    $id = table()->nextId('Id');
    $result = table()->insert(['Id' => $id, 'FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $tf->assertEquals($id, 3);
});

$tf->test("Testing lastInsertId", function ($tf) {
    //db()->debug = true;

    $result = table()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = table()->lastInsertId('Id');
    $tf->assertEquals($id, 1);

    $result = table()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = table()->lastInsertId('Id');
    $tf->assertEquals($id, 2);

    $result = table()->insert(['FirstName' => 'John', 'LastName' => 'Doe']);
    $tf->assertTrue($result);
    $id = table()->lastInsertId('Id');
    $tf->assertEquals($id, 3);
});

$tf->test("Testing WHERE clauses", function ($tf) {
    //db()->debug = true;

    $result = table()->insert(['FirstName' => 'John', 'LastName' => 'Doe');
    $tf->assertTrue($result);

    $result = table()->insert(['FirstName' => 'Ben', 'LastName' => 'Smith');
    $tf->assertTrue($result);

    $result = table()->insert(['FirstName' => 'Tom', 'LastName' => 'Johnson');
    $tf->assertTrue($result);

    $result = table()->insert([['FirstName' => 'Sean', 'LastName' => 'Farah');
    $tf->assertTrue($result);

    $result = table()->where('FirstName', '=', 'Tom')->select();
    $tf->assertEquals(count($result), 1);

    $result = table()->where('FirstName', '=', 'Ben')->where('FirstName', '=', 'Sean', 'OR')->select();
    $tf->assertEquals(count($result), 2);

    $lastestSql = array_pop(db()->sqlLog);

    $tf->assertEquals($lastestSql, "SELECT * FROM 'tests' WHERE FirstName = 'Ben' OR FirstName = 'Sean';");
});

$tf();
