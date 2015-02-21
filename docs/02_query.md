# StorageQuery

`StorageQuery` should be sufficient to handle all _CRUD_ operations and much more.
Operations described below assume that entity classes, models and tables exist.

## Create instance

`StorageQuery` instance is dependant on `DriverInterface` and `QueryBuilderInterface`.
First one grants access to database engine, second one - builds queries in databases language.

```php
$dsn = sprintf('%s:dbname=%s;host=%s;port=%u', 'mysql', 'database', 'localhost', 3306);
$Converter = new \Moss\Storage\Driver\Converter();
$driver = new \Moss\Storage\Driver\PDO($dsn, 'user', 'password', $Converter);

$builder = new \Moss\Storage\Builder\MySQL\QueryBuilder();

$storage = new \Moss\Storage\StorageQuery($driver, $builder);
$storage->register('...'); // register models
```

**Important**
You must register models, without them storage will be unable to work.

***Important***
`Converter` is used to cast entity values into/from proper type/format eg \DateTime instance into timestamp or array into serialized string.
When you don't use

## Execute and queryString

When `::execute()` method is called, build query is sent to database and executed. Database response is processed and returned according to operation type.
In any moment you can call `::queryString()` method to retrieve array with current query string and all bound values.

## Operations

Each operation will be described as SQL query, PHP example and result type.

### Num

Returns number of entities that will be read by query (reads only primary keys).
Result: `integer`

```sql
SELECT ... FROM ...
```
```php
$count = $storage
	->num('entity')
	->execute();
```

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
### Clear

Removes all entities from storage (just like truncate table)
Result: `bool`

```sql
TRUNCATE TABLE ...
```
```php
$entity = new Entity();
$bool = $storage
	->clear('entity)
	->execute();
```

## Operation modifiers

Storage provides modifiers for operations, such as `where`, `having`, `limit`, `order`, `aggregate` etc.

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

### Aggregate

When needed, data can be aggregated and read with rest of entity.

```php
$result = $storage->read('entity')
	->aggregate($method, $field)
	->group($field)
	->execute();
```

Where:

 * `$method` is one of supported methods:
    * `distinct`
    * `count`
    * `avg`
    * `max`
    * `min`
    * `sum`
 * `$field` aggregated property

Or use alias for above methods

```php
$result = $storage->read('entity')
	->count($field)
	->group($field)
	->execute();
```

`Group` method is optional.

## Relations

By using relations you can read entire object structures, article with author, comments and tag in single query.
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
		->with(array('author', 'comment', 'tag'))
		->execute();
```

To read comments with their authors:

```php
	$result = $storage->read('article')
		->with(array('author', 'comment.author', 'tag'))
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
Conditions are represented as arrays with values in same order as those passed to `::where()` method.

```php
	$result = $storage->read('article')
		->with('comment', array(array('published' => true)))
		->execute();
```

This will read only published comments for articles.

### Sorting

The `::where()` method has third argument used to sort entities in relation.

```php
	$result = $storage->read('article')
		->with('comment', array(array('published', true)), array('created', 'desc'))
		->execute();
```

### Query

If there's a need for more complicated conditions, calling the `::relation($relation)` method will return `Relation` representing `$relation`, that can be accessed for `Query`

```php
	$storage = $storage->read('article');
	$storage->with(array('comment', 'author', 'tag'));

	$commentRelation = $storage->relation('comment');
	$commentQuery = $commentRelation->query();

	$commentQuery->condition(....);

	$result = $storage->execute();
```

Or simpler way:

```php
	$storage = $storage->read('article')->with(array('comment', 'author', 'tag'));
	$storage->relation('comment')->query()->condition(....);
	$result = $storage->execute();
```

## Join

When relation definition exists, it is possible to join data from relating entity.

```sql
SELECT ... FROM ... LEFT OUTER JOIN ...
```
```php
$result = $storage->read('entity')
	->join('left', 'other')
	->execute();
```

Available join methods

 * `inner` (alias `innerJoin`) - inner join
 * `left` (alias `leftJoin`) - left outer join
 * `right` (alias `rightJoin`) - right outer join

After joining other entity data into query, all its fields can be read and even aggregated:
With `leftJoin` alis:

```sql
SELECT COUNT(`others`.`id`) AS `others`, ... FROM ... LEFT OUTER JOIN ... GROUP BY `entity`.`id`
```
```php
$result = $storage->read('entity')
	->count('other.id', `others`)
	->leftJoin('other')
	->group('id')
	->execute();
```

Above query will read from `entity`, each collection element will have a number of relating `other` elements.
Data from other entities will be available as public properties in read instances, unless such properties exist and are defined as private/public.
