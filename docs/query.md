# Query

`Query` should be sufficient to handle all _CRUD_ operations.
If something more sophisticated is needed, you can access driver directly and write queries by hand.

Operations described below assume that entity classes, models and containers exist.

### Count

Returns number of entities that will be read by query (reads only primary keys).

	/* SELECT ... FROM ... */
	$count = $storage->count('entity')
		->execute();

### Read & read one

Reads entities matching conditions, returns array of read entities

	/* SELECT ... FROM ... */
	$result = $storage->read('entity')
		->execute();

Reads only first matching entity, will throw exception if none found.

	/* SELECT ... FROM ... LIMIT 1 */
	$entity = $storage->readOne('entity')
		->execute();

### Insert

Inserts entity into storage, will update passed entity primary keys

	/* INSERT INTO ... VALUES ... */
	$entity = new entity();
	$bool = $storage->insert($entity)
		->execute();

### Update

Updates existing entity

	/* UPDATE ... SET ... */
	$entity = new entity();
	$entity = $storage->update($entity)
		->execute();

### Write

Writes entity, if entity with same primary keys exists will be updated, otherwise inserts new.
Returns entity with updated primary fields

	$entity = new entity();
	$entity = $storage->write($entity)
		->execute();

### Delete

Removes entity from storage, also removes values from entity primary fields

	/* DELETE FROM ... WHERE */
	$entity = new entity();
	$entity = $storage->delete($entity)
		->execute();

### Clear

Removes all entities from storage (just like truncate table)

	/* TRUNCATE TABLE ... */
	$entity = new entity();
	$bool = $storage->clear('entity)
		->execute();

## Operation modifiers

Storage provides modifiers for operations, such as `where`, `having`, `limit`, `order`, `aggregate` etc.

### Conditions

The `where` and `having` clauses allow to add as many conditions as needed to count/read operations.
Both work in same way, accept same kind of attributes.

	/* SELECT ... FROM ... WHERE [condition] */
	$result = $storage->read('entity')
		->where($field, $value, $comparison, $logical)
		->execute();

	/* SELECT ... FROM ... HAVING [condition] */
	$result = $storage->read('entity')
		->having($field, $value, $comparison, $logical)
		->execute();

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

	/* ... WHERE (`foo` = 'bar') */
    $result = $storage->read('entity')
        ->where('foo', 'bar')
        ->execute();

    /* ... WHERE (`foo` = 'bar' OR `foo` = 'yada') */
    $result = $storage->read('entity')
	    ->where('foo', array('bar', 'yada'))
	    ->execute();

    /* ... WHERE (`foo` = 'bar') OR (`foo` = 'yada') */
    $result = $storage->read('entity')
	    ->where('foo', 'bar', '=', 'or')
	    ->where('bar', 'yada')
	    ->execute();

    /* ... WHERE (`foo` = 'bar' OR `bar` = 'yada') */
    $result = $storage->read('entity')
	    ->where(array('foo', 'bar'), 'yada')
	    ->execute();

    /* ... WHERE (`foo` = 'bar') OR (`bar` = 'yada') */
    $result = $storage->read('entity')
	    ->where('foo', 'yada', '=', 'or')
	    ->where('bar', 'yada')
	    ->execute();

    /* ... WHERE (`foo` = 'foofoo' OR `bar` = 'barbar') */
    $result = $storage->read('entity')
	    ->where(array('foo', 'bar'), array('foofoo', 'barbar'))
	    ->execute();

    /* ... WHERE (`foo` = 'foofoo') OR (`bar` = 'barbar') */
    $result = $storage->read('entity')
        ->where('foo', 'foofoo', '=', 'or')
	    ->where('bar', 'barbar')
	    ->execute();

### Order

To set order for operation type:

	/* ... ORDER BY field ASC, otherfield DESC */
	$result = $storage->read('entity')
		->order('field', 'asc')
		->order('otherfield', 'desc')
		->execute();

### Limit

Limiting operation result

	/* ... LIMIT 30,60 */
    $result = $storage->read('entity')
        ->limit(30,60)
        ->execute();

### Fields

Allows to restrain read fields.

	/* SELECT `id`, `title`, `slug` FROM ... */
	$result = $storage->read('entity')
		->fields(array('id', 'title', 'slug'))
		->execute();

### Aggregate

When needed, data can be aggregated and read with rest of entity.

	$result = $storage->read('entity')
		->aggregate($method, $field)
		->group($field)
		->execute();

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

	$result = $storage->read('entity')
		->count($field)
		->group($field)
		->execute();

### Join

It is possible to join data from other entities into one query.

	$result = $storage->read('entity')
		->count('other.id', `others`)
		->join('left', 'other')
		->group('id')
		->execute();

Or use alias:

	$result = $storage->read('entity')
		->count('other.id', `others`)
		->leftJoin('other')
		->group('id')
		->execute();

Above query will read from `entity`, each collection element will have a number of relating `other` elements.

Currently supported are 3 join methods:

 * `inner` - inner join
 * `left` - left outer join
 * `right` - right outer join

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