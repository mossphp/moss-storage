# Query

`Query` should be sufficient to handle all _CRUD_ operations.
If something more sophisticated is needed, you can access driver directly and write queries by hand.

Operations described below assume that entity classes, models and tables exist.

## Execute and queryString

When `::execute()` method is called, build query is sent to database and executed.
But in any moment you can call `::queryString()` method to retrieve array with build query string and all bind values.

## Count

Returns number of entities that will be read by query (reads only primary keys).

```sql
SELECT ... FROM ...
```
```php
$count = $storage->count('entity')
	->execute();
```

## Read & read one

Reads entities matching conditions, returns array of read entities

```sql
SELECT ... FROM ...
```
```php
$result = $storage->read('entity')
	->execute();
```

Reads only first matching entity, will throw exception if none found.

```sql
SELECT ... FROM ... LIMIT 1
```
```php
$entity = $storage->readOne('entity')
	->execute();
```

## Insert

Inserts entity into storage, will update passed entity primary keys

```sql
INSERT INTO ... VALUES ...
```
```php
$entity = new Entity();
$bool = $storage->insert($entity)
	->execute();
```

## Update

Updates existing entity

```sql
UPDATE ... SET ...
```
```php
$entity = new Entity();
$entity = $storage->update($entity)
	->execute();
```

## Write

Writes entity, if entity with same primary keys exists will be updated, otherwise inserts new.
Returns entity with updated primary fields

```php
$entity = new Entity();
$entity = $storage->write($entity)
	->execute();
```

## Delete

Removes entity from storage, also removes values from entity primary fields

```sql
DELETE FROM ... WHERE
```
```php
	$entity = new Entity();
	$entity = $storage->delete($entity)
		->execute();

## Clear

Removes all entities from storage (just like truncate table)

```sql
TRUNCATE TABLE ...
```
```php
$entity = new Entity();
$bool = $storage->clear('entity)
	->execute();
```

## Operation modifiers

Storage provides modifiers for operations, such as `where`, `having`, `limit`, `order`, `aggregate` etc.

### Conditions

The `where` and `having` clauses allow to add as many conditions as needed to count/read operations.
Both work in same way, accept same kind of attributes.

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
    * `regex` - regex
 * `$logical`:
    * `and` - and (default)
    * `or` - or

Examples:

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

Allows to restrain read fields.

```sql
SELECT `id`, `title`, `slug` FROM ...
```
```php
$result = $storage->read('entity')
	->fields(array('id', 'title', 'slug'))
	->execute();
```

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

## Relations

By using relations you can read entire object structures, article with author, comments and tag in single query.
To use relation, it must be defined in entity model, the rest is easy:
Assuming that required models and relations exists:

	$result = $storage->read('article')
		->relation('author')
		->relation('comment')
		->relation('tag')
		->execute();

To read comments with their authors:

	$result = $storage->read('article')
		->relation('author')
		->relation('comment.author')
		->relation('tag')
		->execute();

To set additional conditions, sorting order to relation, access its query:

    $query = $storage->read('article')
    		->relation('author')
    		->relation('comment.author')
    		->relation('tag');

    $query->relQuery('comment')->where('isSpam', false);

	$result = $query->execute();

The above query will read all mentioned before, but without comments flagged as spam.

### Join

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