<?php

define("ENVIRONMENT", 'testing');

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/helper.php';

$tf = new \Testify\Testify("SqlDb Test Suite");

$tf->beforeEach(function ($tf) {
    tableSqlite()->drop();

    tableSqlite()->column('Id', 'INTEGER', 'AUTOINCREMENT')
        ->column('FirstName', 'STRING')
        ->column('MiddleNames', 'STRING')
        ->column('LastName', 'STRING')
        ->column('Birthday', 'DATETIME', 'DEFAULT NULL')
        ->create();
    
    tableMySql()->drop();

    tableMySql()->column('Id', 'INTEGER', 'AUTOINCREMENT')
        ->column('FirstName', 'STRING')
        ->column('MiddleNames', 'STRING')
        ->column('LastName', 'STRING')
        ->column('Birthday', 'DATETIME', 'DEFAULT NULL')
        ->create();
});

require __DIR__ . '/mysql_tests.php';
require __DIR__ . '/sqlite_tests.php';

$tf();
