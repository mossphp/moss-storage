# StorageQuery

`Query` should be sufficient to handle all _CRUD_ operations and much more.
Operations described below assume that entity classes, models and tables exist.

## Create instance

`Query` instance is dependant on `Connection` and `ModelBag`.
First one grants access to database engine, second one - holds all registered models.

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

$storage = new Storage($conn, $models);

```

**Important**
You must register models, without them storage will be unable to work.

## Execute and queryString

When `::execute()` method is called, build query is sent to database and executed. Database response is processed and returned according to operation type.
In any moment you can call `::queryString()` method to retrieve array with current query string and all bound values.

## Operations

Each operation will be described as SQL query, PHP example and result type.

### Read & read one

Reads entities matching conditions, returns array of read entities
Result: `array` of entities

```sql
SELECT ... FROM ...
```
```php
$result = $storage
	->read('entity')
	->execute();
```

Reads number of entities matching conditions.
Result: `number`
```sql
SELECT ... FROM ...
```
```php
$result = $storage
	->read('entity')
	->count();
```

Reads only first matching entity, will throw exception if entity does not exists.
Result: `entity`

```sql
SELECT ... FROM ... LIMIT 1
```
```php
$entity = $storage
	->readOne('entity')
	->execute();
```

### Insert

Inserts entity into storage, will update passed entity primary keys
Result: `entity`

```sql
INSERT INTO ... VALUES ...
```
```php
$entity = new Entity();
$bool = $storage
	->insert($entity)
	->execute();
```

### Update

Updates existing entity
Result: `entity`

```sql
UPDATE ... SET ...
```
```php
$entity = new Entity();
$entity = $storage
	->update($entity)
	->execute();
```

### Write

Writes entity, if entity with same primary keys exists will be updated, otherwise inserts new.
Returns entity with updated primary keys
Result: `entity`

```php
$entity = new Entity();
$entity = $storage
	->write($entity)
	->execute();
```

### Delete

Removes entity from storage, also removes values from entity primary keys
Result: `entity`

```sql
DELETE FROM ... WHERE
```
```php
	$entity = new Entity();
	$entity = $storage
		->delete($entity)
		->execute();
```
## Operation modifiers

Storage provides modifiers for operations, such as `where`, `having`, `limit`, `order` etc.

### Conditions

The `where` and `having` clauses allow to add as many conditions as needed to count/read operations.
Both work in same way, accept same kind of attributes, but `having` allows to refer to aggregation results.

```sql
SELECT ... FROM ... WHERE [condition]
```
```php
$result = $storage->read('entity')
	->where($field, $value, $comparison, $logical)
	->execute();
```

```sql
SELECT ... FROM ... HAVING [condition]
```
```php
$result = $storage->read('entity')
	->having($field, $value, $comparison, $logical)
	->execute();
```

Where

 * `$field` contains property name (or array of properties) included in conditions
 * `$value` is a value (or array of values) for comparison
 * `$comparison` must be supported comparison operator:
    * `=` - equal (default)
    * `!=` - not equal
    * `<` - less than
    * `>` - greater than
    * `>=` - less or equal than
    * `<=` - greater or equal than
    * `like` - like
    * `regex` - regex (case insensitive)
 * `$logical`:
    * `and` - and (default)
    * `or` - or

Examples with SQL representation:

```sql
... WHERE (`foo` = 'bar')
```
```php
$result = $storage->read('entity')
    ->where('foo', 'bar')
    ->execute();
```

```sql
... WHERE (`foo` = 'bar' OR `foo` = 'yada')
```
```php
$result = $storage->read('entity')
    ->where('foo', array('bar', 'yada'))
    ->execute();
```

```sql
... WHERE (`foo` = 'bar') OR (`foo` = 'yada')
```
```php
$result = $storage->read('entity')
    ->where('foo', 'bar', '=', 'or')
    ->where('bar', 'yada')
    ->execute();
```

```sql
... WHERE (`foo` = 'bar' OR `bar` = 'yada')
```
```php
$result = $storage->read('entity')
    ->where(array('foo', 'bar'), 'yada')
    ->execute();
```

```sql
... WHERE (`foo` = 'bar') OR (`bar` = 'yada')
```
```php
$result = $storage->read('entity')
    ->where('foo', 'yada', '=', 'or')
    ->where('bar', 'yada')
    ->execute();
```

```sql
... WHERE (`foo` = 'foofoo' OR `bar` = 'barbar')
```
```php
$result = $storage->read('entity')
    ->where(array('foo', 'bar'), array('foofoo', 'barbar'))
    ->execute();
```

```sql
... WHERE (`foo` = 'foofoo') OR (`bar` = 'barbar')
```
```php
$result = $storage->read('entity')
    ->where('foo', 'foofoo', '=', 'or')
    ->where('bar', 'barbar')
    ->execute();
```

### Order

To set order for operation type:

```sql
... ORDER BY field ASC, otherfield DESC
```
```php
$result = $storage->read('entity')
	->order('field', 'asc')
	->order('otherfield', 'desc')
	->execute();
```

Also, you can force order by passing array of values as second argument, eg:

```php
$result = $storage->read('entity')
	->order('field', array(1,3,2)
	->execute();
```

This will return `1` as first, `3` as second and `2` as third.


### Limit

Limiting operation result

```sql
... LIMIT 30,60
```
```php
$result = $storage->read('entity')
    ->limit(30,60)
    ->execute();
```

### Fields

Allows to restrain read fields, if for any reason you don't need all data.

```sql
SELECT `id`, `title`, `slug` FROM ...
```
```php
$result = $storage->read('entity')
	->fields(array('id', 'title', 'slug'))
	->execute();
```

There is also similar command for restraining fields in `write`/`insert`/`update` operations

```php
$result = $storage->write('entity')
	->values(array('id', 'title', 'slug'))
	->execute();
```

Only `id`, `title` and `slug` will be written.

## Relations

By using relations you can read entire object structures, article with author, comments and tags in single query.
To use relation, it must be defined in entity model, the rest is easy, just use the `::with()` method.

Assuming that required models and relations exists:

```php
	$result = $storage->read('article')
		->with('author')
		->execute();
```

Or in case of many relations:

```php
	$result = $storage->read('article')
		->with(['author', 'comment', 'tag'])
		->execute();
```

To read comments with their authors:

```php
	$result = $storage->read('article')
		->with(['author', 'comment.author', 'tag'])
		->execute();
```

### Filtering

Entities read in relations can be filtered by passing additional conditions in relation:

```php
	$result = $storage->read('article')
		->with('comment', $relationConditions)
		->execute();
```

Where `$relationCondition` is an array containing conditions for entities read in relation.
Conditions are represented as arrays with values in same order as arguments passed to `::where()` method.

```php
	$result = $storage->read('article')
		->with('comment', [['published', true]])
		->execute();
```

This will read only published comments for articles.

### Sorting

The `::with()` method has third argument used to sort entities in relation.

```php
	$result = $storage->read('article')
		->with('comment', [['published', true]], ['created', 'desc'])
		->execute();
```
