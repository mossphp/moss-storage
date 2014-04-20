# Schema

`StorageSchema` responsibility is to create, alter and drop entity tables.
Since `StorageSchema` can use multiple queries to perform required task, it will always return array containing result for each used query

Each `StorageSchema` operation can be called for all modeled tables or for just one.
Note, that not all operations can be executed on just one table (eg when foreign keys are used).

## Create instance

`StorageSchema` depends on `DriverInterface` and `SchemaBuilderInterface`.

```php
$dsn = sprintf('%s:dbname=%s;host=%s;port=%u', 'mysql', 'database', 'localhost', 3306);
$driver = new \Moss\Storage\Driver\PDO($dsn, 'user', 'password');

$builder = new \Moss\Storage\Builder\MySQL\SchemaBuilder();

$storage = new \Moss\Storage\StorageSchema($driver, $builder);
$storage->register('...'); // register models
```

## Query string & Execute

When `::execute()` method is called, `StorageSchema` builds all queries and sends them to database to execute them.
To check what queries will be executed without sending them to database, instead `::execute()` call `::queryString()`.
This will return array containing all build queries in same order that they are sent to database.

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
This operation should be performed for entire repository - foreign keys from other tables can block alterations.

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

## Drop

Drops entity table

```sql
DROP TABLE IF EXISTS ...
```
```php
$storage
	->drop('entity')
	->execute();
```

`$result` will contain all executed queries.