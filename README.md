# Storage

## Usage

	$Driver = new \moss\storage\driver\PDO('mysql', 'database', 'username', 'password');
	$Adapter = new \moss\storage\adapter\MySQL($Driver);
	$Storage = new \moss\storage\Storage($Adapter);

	$Storage->registerModel(
	    new \moss\storage\model\Model(
	        '\stdClass',
	        'std_class',
	        array(
	             new \moss\storage\model\definition\Field('id', 'integer', array('unsigned', 'auto_increment')),
	             new \moss\storage\model\definition\Field('integer', 'integer'),
	             new \moss\storage\model\definition\Field('string', 'string'),
	             new \moss\storage\model\definition\Field('numeric', 'decimal', array('length' => 4, 'precision' => 2)),
	             new \moss\storage\model\definition\Field('datetime', 'datetime'),
	             new \moss\storage\model\definition\Field('serial', 'serial', array('null')),
	             new \moss\storage\model\definition\Field('bool', 'boolean', array('default' => 0, 'null'))
	        ),
	        array(
	             new \moss\storage\model\definition\Index('primary', array('id'), 'primary'),
	        )
	    )
	);

	/* DROP TABLE IF EXISTS `std_class` */
	$Storage
	    ->drop('\stdClass')
	    ->execute();

	/*
	CREATE TABLE `std_class` (
		`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`integer` INT(10) NOT NULL,
		`string` TEXT NOT NULL,
		`numeric` DECIMAL(4,2) NOT NULL,
		`datetime` DATETIME NOT NULL,
		`serial` TEXT COMMENT 'serial' DEFAULT NULL,
		`bool` TINYINT(1) COMMENT 'boolean' DEFAULT 0 ,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
	*/
	$Storage
	    ->create('\stdClass')
	    ->execute();

	$Entity = new stdClass();
	$Entity->integer = 1;
	$Entity->string = 'foo';
	$Entity->numeric = 44.222;
	$Entity->datetime = new DateTime();
	$Entity->serial = array(1, 2, 3);

	/*
	INSERT INTO `std_class` (
		`integer`,
		`string`,
		`numeric`,
		`datetime`,
		`serial`,
		`bool`
	) VALUES (
		:value_0_integer,
		:value_1_string,
		:value_2_numeric,
		:value_3_datetime,
		:value_4_serial,
		:value_5_bool
	)
	*/
	$Storage
	    ->write($Entity)
	    ->execute();

## Query

## Supported operations

Operations described below assume that entity models, classes and containers exist.

### Check

Checks if data container for entity exists (does not check if is up-to-date)

	$bool = $Storage
		->check('\SomeEntity')
		->execute();

### Create

Creates data container for entity based on its model

	/* CREATE TABLE ... */
	$Storage
		->create('\SomeEntity')
		->execute();

### Alter

Updates existing data container to match current model

	/* ALTER TABLE ... */
	$Storage
		->alter('\SomeEntity')
		->execute();

### Drop

Drops entity container

	/* DROP TABLE IF EXISTS ... */
	$Storage
		->drop('\SomeEntity')
		->execute();

### Count

Returns number of entities that will be read by query (reads only primary keys).

	/* SELECT ... WHERE ... */
	$count = $Storage
		->count('\SomeEntity')
		[->condition(..)]
		->execute();

### Read & read one

Reads entities matching conditions, returns array of read entities

	/* SELECT ... WHERE ... ORDER ... LIMIT ... */
	$entities = $Storage
		->read('\SomeEntity')
		[->condition(..)]
		[->order(..)]
		[->limit(..)]
		->execute();

Reads only first matching entity, will throw exception if none found.

	/* SELECT ... WHERE ... ORDER ... LIMIT 1 */
	$Entity = $Storage
		->readOne('\SomeEntity')
		[->condition(..)]
		[->order(..)]
		->execute();

### Insert

Inserts entity into storage, will update passed entity primary keys

	/* INSERT INTO ... VALUES ... */
	$Entity = new \SomeEntity();
	$bool = $Storage
		->insert($Entity)
		->execute();

### Update

Updates existing entity

	/* UPDATE ... SET ... */
	$Entity = new \SomeEntity();
	$Entity = $Storage
		->update($Entity)
		->execute();

### Write

Writes entity, if entity with same primary keys exists will be updated, otherwise inserts new.
Returns entity with updated primary fields

	$Entity = new \SomeEntity();
	$Entity = $Storage
		->write($Entity)
		->execute();

### Delete

Removes entity from storage, also removes values from entity primary fields

	/* DELETE FROM ... WHERE */
	$Entity = new \SomeEntity();
	$Entity = $Storage
		->delete($Entity)
		->execute();

### Clear

Removes all entities from storage (just like truncate table)

	/* TRUNCATE TABLE ... */
	$Entity = new \SomeEntity();
	$bool = $Storage
		->clear('\SomeEntity)
		->execute();

## Operation modifiers

Storage provides modifiers for operations, such as `condition`, `limit`, `order`, `aggregate`.

### Conditions

The `condition` method allows to add as many conditions as needed to count/read operations.

	$entities = $Storage
		->read('\SomeEntity')
		->condition($field, $value, $comparisonOperator, $logicalOperator)
		->execute();

Where

 * `$field` contains property name (or array of properties) included in conditions
 * `$value` is a value (or array of values) for comparison
 * `$comparisonOperator` must be supported comparison operator:
    * `==` - equal (default)
    * `!=` - not equal
    * `<` - less than
    * `>` - greater than
    * `>=` - less or equal than
    * `<=` - greater or equal than
    * `%%` - like
 * `$logicalOperator`:
    * `&&` - and
    * `||` - or

Examples:

	/* ... WHERE (`foo` = 'bar') */
    $entities = $Storage
        ->read('\SomeEntity')
        ->condition('foo', 'bar')
        ->execute();

    /* ... WHERE (`foo` = 'bar' OR `foo` = 'yada') */
    $entities = $Storage
	    ->read('\SomeEntity')
	    ->condition('foo', array('bar', 'yada'))
	    ->execute();

    /* ... WHERE (`foo` = 'bar') OR (`foo` = 'yada') */
    $entities = $Storage
	    ->read('\SomeEntity')
	    ->condition('foo', 'bar', '==', '||')
	    ->condition('bar', 'yada')
	    ->execute();

    /* ... WHERE (`foo` = 'bar' OR `bar` = 'yada') */
    $entities = $Storage
	    ->read('\SomeEntity')
	    ->condition(array('foo', 'bar'), 'yada')
	    ->execute();

    /* ... WHERE (`foo` = 'bar') OR (`bar` = 'yada') */
    $entities = $Storage
	    ->read('\SomeEntity')
	    ->condition('foo', 'yada', '==', '||')
	    ->condition('bar', 'yada')
	    ->execute();

    /* ... WHERE (`foo` = 'foofoo' OR `bar` = 'barbar') */
    $entities = $Storage
	    ->read('\SomeEntity')
	    ->condition(array('foo', 'bar'), array('foofoo', 'barbar'))
	    ->execute();

    /* ... WHERE (`foo` = 'foofoo') OR (`bar` = 'barbar') */
    $entities = $Storage
	    ->read('\SomeEntity')->condition('foo', 'foofoo', '==', '||')
	    ->condition('bar', 'barbar')
	    ->execute();

### Order

To set order for operation type:

	/* ... ORDER BY field ASC, otherfield DESC */
	$result = $Storage
		->read('\SomeEntity')
		->order('field', 'asc')
		->order('otherfield', 'desc')
		->execute();

### Limit

Limiting operation result

	/* ... LIMIT 30,60 */
    $result = $Storage
        ->read('\SomeEntity')
        ->limit(30,60)
        ->execute();

### Fields

Allows to restrain read fields.

	/* SELECT `id`, `title`, `slug` FROM ... */
	$result = $Storage
		->read('\SomeEntity')
		->fields(array('id', 'title', 'slug'))
		->execute();

### Aggregate

When needed, data can be aggregated and read with rest of entity.

	$result = $Storage
		->read('\SomeEntity')
		->aggregate($method, $field, $group)
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
 * `$group` grouping property

## Relations
### One-To-One
### One-To-Many
#### Transparency

## Model

Model represents entity structure, how properties are mapped to database structure, what indexes exist and how possible relations.

Each `Model` consists of:

 * container/table name
 * namespaced entity class name
 * any number of field definitions
 * any number of index definitions
 * any number of relation definitions

To create model type:

	$fields = array(...); /* array containing field definitions
	$indexes = array(...); /* array containing index definitions
	$relations = array(...); /* another array with relation definitions
	$SomeModel = new \moss\storage\model\Model('someTable', '\some\Entity', $fields, $indexes, $relations)

Each entity class must have separate model and model must be registered in `Storage`:

	$Storage = new Storage($Adapter);
	$Storage->register($SomeModel);

### Fields

Object mapping means that entity properties are represented as container/table fields.
Therefore `Model` must contain field definitions:

	$Field = new Field($field, $type, $attributes, $mapping);

Where `$field` is property name, `$type` parameter defines the type of field, it can be:

 * `boolean` - for logical values
 * `integer` - for ... integers
 * `decimal` - for floating point, fractions,
 * `string` - for chars, strings and longer texts,
 * `datetime` - for dates, time,
 * `serial` - for arrays, objects etc.

Array `$attributes` containing attributes, should contain all additional field attributes.
Supported attributes:

  * `null` - marks field as nullable
  * `default` - sets default value
  * `auto_increment` - field is auto incremented
  * `unsigned` - unsigned numeric
  * `length` - field length
  * `precision` - field precision (used only in numeric)

Parameter `$mapping` is provided for situations where field name is different than property name.
For instance property `companyAddress` can be represented as `company_address` field.

For example - define decimal field, with 4 digits, two of which on the fractional, in result represented as `fooBar` in container `foo_bar`:

	$Field = new Field('fooBar', 'decimal', array('null', 'length' => 4, 'precision' => 2), 'foo_bar');

### Indexes

Currently only primary indexes are significant.
They are used for creating insert/update/write/delete queries.

Each index consist of its name, type and indexed fields:

	$fields = array('id');
	$Index = new Index($name, $fields, $type)

Supported index types:

 * `primary` - primary index, primary are always unique
 * `unique` - unique index,
 * `index` - any other index

### Relations

Relations describe what other entities can be contained inside entity.
`Storage` supports only two relations:

 * `one` - one-to-one relation, where entity points to exactly one entity,
 * `many` - one-to-many relation, entity points to many entities

Both relations are defined in same way:

	$Relation = new Relation($entity, $type, $keys, $localValue, $referencedValue, $container);

 * `$entity` - namespaced entity class pointed by relation
 * `$type` - relation type, `one` or `many`
 * `$keys` - array containing local fields as keys and referenced fields as corresponding values
 * `$localValue` - array with fields as keys ant values
 * `$referencedValue` - same as above, but for referenced table
 * `$container` - entity field where relation entities exist, if not set - field will be same as entity class (without namespace)

For example, one `BlogEntry` can contain `Author` entity and many `Comment` entities.
To retrieve them in one operation two relations must be defined: one-to-one for `Author` and one-to-many for `Comment`:

	$AuthorRelation = new Relation('\Author', 'one', array('author_id' => 'id'));
	$CommentRelation = new Relation('\Comment', 'many', array('id' => 'entry_id'), array(), array('visible' => 1), 'Comments');

`Author` will be available in `Author` property.
All `Comment` entities with `visibility` property equal to `1` will be placed in `Comments` property.

**Important**
Relations are unidirectional, therefore if `Author` should point to `BlogEntry`, new relation in `Author` model must be defined.

## Adapter

## Driver