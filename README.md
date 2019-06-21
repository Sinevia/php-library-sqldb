# PHP Library SqlDB

PHP Library for working with SQL databases.

![No Dependencies](https://img.shields.io/badge/no-dependencies-success.svg)
[![Build status][build-status-master-image]][build-status-master]
[![GitHub stars](https://img.shields.io/github/stars/Sinevia/php-library-sqldb.svg?style=social&label=Star&maxAge=2592000)](https://GitHub.com/Sinevia/php-library-sqldb/stargazers/)
[![HitCount](http://hits.dwyl.io/Sinevia/badges.svg)](http://hits.dwyl.io/Sinevia/badges)

[build-status-master]: https://travis-ci.com/Sinevia/php-library-sqldb
[build-status-master-image]: https://api.travis-ci.com/Sinevia/php-library-sqldb.svg?branch=master

## Features ##
- MySQL, SQlite and SQLiteDB (SQLite in the cloud) supported
- Unified data types. The data types are developer orientated (string, text, integer, float, blob). These are then translated to the correct column type for the corresponding database.
- Fluent interface for building queries

## Installation ##

1) Via composer command line
```sh
composer require sinevia/php-library-sqldb
```

2) Via composer file:

Add the latest stable version to your composer file, and update via composer

```json
"require": {
    "sinevia/php-library-sqldb": "2.6.0"
}
```

## Data Types ##

| Data Type | MySLQ Data Type | SQLite Data Type |
|-----------|-----------------|------------------|
| STRING    | VARCHAR (255)   | TEXT             |
| TEXT      | LONG TEXT       | TEXT             |
| INTGER    | BIG INT         | INTEGER          |
| FLOAT     | DOUBLE          | REAL             |
| BLOB      | LONG BLOB       | TEXT             |



## Usage ##

### 1) Creating a new database instance ###


```php
// MySQL
$db = new Sinevia\SqlDB(array(
    'database_type'=>'mysql',
    'database_name'=>'db_name',
    'database_host'=>'db_host',
    'database_user'=>'db_user',
    'database_pass'=>'db_pass'
));

// SQLite (creating a new SQLite database, if it does not exist)
$db = new Sinevia\SqlDB(array(
    'database_type'=>'sqlite',
    'database_name'=>'db_name',
    'database_host'=>'db_host',
    'database_user'=>'db_user',
    'database_pass'=>'db_pass'
));

// SQLiteDB (SQLite in the cloud)
$db = new Sinevia\SqlDB(array(
    'database_type'=>'sqlitedb',
    'database_host'=>'sqlitedb_api_url',
    'database_pass'=>'sqlitedb_api_key'
));
```

### 2) Drop a database ###

Depending on your database hosting this may or may not be supported

```php
// Dropping a database
$db->drop();
```

### 3) Creating a new table ###

```php
// Check if table already exists?
if ($db->table("person")->exists() == false) {
    // Create table
    $db->table("person")
        ->column("Id", "INTEGER", "NOT NULL PRIMARY KEY AUTO_INCREMENT")
        ->column("FirstName", "STRING")
        ->column("LastName", "STRING")
        ->create();
}
```

### 3) Drop a table ###

```php
// Dropping a table
$isOk = $db->table("person")->drop();
```

### 3) Inserting rows ###

```php
$isOk = $db->table('person')->insert([
    'FirstName' => 'Peter',
    'LastName' => 'Pan',
]);

// Getting the new autoincremented ID
$personId = $db->lastInsertId();
```


### 4) Selecting rows ###

```php
//Selects all the rows from the table
$rows = $db->table("person")->select();

// Selects the rows where the column NAME is different from Peter, in descending order
$rows = $db->table("person")
    ->where("Name", "!=", "Peter")
    ->orderby("Name","desc")
    ->select();
```

### 5) Updating rows ###

```php
// Delete row by ID
$isOk = $db->table("person")
    ->where("Id", "==", "1")
    ->update([
        'LastName' => 'Voldemort'
    ]);
```

### 5) Deleting rows ###

```php
// Delete row by ID
$isOk = $db->table("person")
    ->where("Id", "==", "1")
    ->delete();

// Delete all rows in the table
$isOk = $db->table("person")->delete();
```

## Helper Functions ##

### 1) Generating UUIDs ###

```php
$uuid = Sinevia\SqlDB::uuid();
```

### 2) Generating HUIDs ###

HUIDs are human friendly unique identifiers, which are date based.

```php
$uid20 = Sinevia\SqlDB::uid(); // 20 digits default
$uid32 = Sinevia\SqlDB::uid(32); // 32 digits
```

# Related Projects #

- [Cache](https://github.com/Sinevia/php-library-sqldb-cache)
- [Monolog](https://github.com/Sinevia/php-library-sqldb-monolog)
- [Tasks](https://github.com/Sinevia/php-library-sqldb-tasks)
