# PHP Library SqlDB

PHP Library for working with SQL databases.

## Background ##
- MySQL and SQLite supported
- Unified column names
  It supports unified column type convention that is more developer orientated. It is then translated to the correct column type for the corresponding database.



## Installation ##

Add the following to your composer file:

```json
   "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/sinevia/php-library-sqldb.git"
        }
    ],
    "require": {
        "sinevia/php-library-sqldb": "dev-master"
    },
```

## Usage ##

### 1) Creating new instance ###


```
#!php

$db = new SqlDB(array(
    'database_type'=>'mysql',
    'database_name'=>'db_name',
    'database_host'=>'db_host',
    'database_user'=>'db_user',
    'database_pass'=>'db_pass'
));
```


### 2) Creating new table ###


```
#!php

if($db->table("user")->exists()==false){
    $database->table("user")->column("Id","INTEGER", "NOT NULL PRIMARY KEY")->column("Name","STRING")->create();
}
```

