# Schema

`Schema` responsibility is to create, alter and drop entity tables.
Since `Schema` can use multiple queries to perform required task, it will always return array containing queries.

Each `Schema` operation can be called for all modeled tables or for just one.
Note, that not all operations can be executed on just one table, especially when there are foreign keys.

## Create instance

`Schema` depends on `Connection` and `ModelBag`.

```php
$conn = DriverManager::getConnection([
    'dbname' => 'test',
    'user' => 'user',
    'password' => 'password',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
    'charset' => 'utf8'
]);

$models = new ModelBag();
$models->set(...); // register some models

$query = new Query($conn, $models);

```

**Important**
A said before, You must register models, without them storage will be unable to work.

## Query string & Execute

When `::execute()` method is called, `Schema` builds all queries and sends them to driver where they are executed.
To check what queries will be executed without sending them to database, instead `::execute()` call `::queryString()`.
This will return array containing all build queries in same order that they are sent to database driver.

## Check

Checks if table for entity exists (does not check if is up-to-date).

```php
$result = $storage
	->check()
	->execute();
```

Will return array with keys as table names and true as value if table exists.


```php
$result = $storage
	->check('entity')
	->execute();
```
Same as above, but with just one element.

## Create
Creates tables (or table) for entity based on its model with all fields, indexes and keys.

```php
$result = $storage
	->create()
	->execute();
```

```php
$result = $storage
	->create('entity')
	->execute();
```

`$result` will contain all executed queries.

## Alter

Updates existing table to match current model.
This operation should be performed for entire repository - otherwise foreign keys from other tables can block alterations.

```php
$storage
	->alter()
	->execute();
```

```php
$storage
	->alter('entity')
	->execute();
```

`$result` will contain all executed queries.

**Important**
You should check what alterations will be performed before executing them - just call `::queryString()`.

## Drop

Drops all defined entity tables

```php
$storage
	->drop()
	->execute();
```

```php
$storage
	->drop('entity')
	->execute();
```

`$result` will contain all executed queries.
