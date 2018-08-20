# PHP Library SqlDB

PHP Library for working with SQL databases.

## Background ##
- MySQL, SQlite and SQLiteDB (SQLite in the cloud) supported
- Unified data types. The data types are developer orientated (string, text, integer, float, blob). These are then translated to the correct column type for the corresponding database.
- Fluent interface for building queries

## Installation ##

1) Via composer command line
```sh
composer require sinevia/php-library-sqldb
```

2) Via composer file:

Add the following to your composer file.

```json
    "require": {
        "sinevia/php-library-sqldb": "dev-master"
    },
```

## Data Types ##

| Data Type | MySLQ Data Type | SQLite Data Type |
|-----------|-----------------|------------------|
| STRING    | VARCHAR         | TEXT             |
| TEXT      | LONG TEXT       | TEXT             |
| INTGER    | BIG INT         | INTEGER          |
| FLOAT     | DOUBLE          | FLOAT            |
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

// Creating a new SQLite Database
$sqlitedb = new Sinevia\SqlDB(array(
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
        ->column("Id", "INTEGER", "NOT NULL PRIMARY KEY")
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
