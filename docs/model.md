# Model

`Model` represents entity structure, how properties are mapped to container, what indexes exist and how modeled entity relate to other.

Each `Model` consists of:

 * namespaced entity class name
 * container/table name
 * any number of field definitions
 * any number of index definitions
 * any number of relation definitions

To create model type:

	$fields = array(...); // array containing field definitions
	$indexes = array(...); // array containing index definitions
	$relations = array(...); // another array with relation definitions
	$SomeModel = new \moss\storage\model\Model(
		'someTable',
		'\some\Entity',
		$fields,
		$indexes,
		$relations
	)

Each entity class must have separate model which must be registered in `Storage` under some alias:

	$storage = new Storage($Adapter);
	$storage->register('alias', $SomeModel);

When creating query you can call entity by namespaced class name or its alias.

## Fields

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

Array `$attributes` containing attributes, should contain all additional field informations.
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

## Indexes & Keys

### Primary key
// todo

### Foreign key
// todo

### Unique
// todo

### Index
// todo

## Relations

Relations describe what other entities can be contained inside entity.
`Storage` supports only two relations:

 * `one` - one-to-one relation, where entity points to exactly one entity,
 * `many` - one-to-many relation, entity points to many entities

Both relations are defined in same way:

	$Relation = new Relation($entity, $type, $keys, $localValue, $referencedValue, $container);

 * `$entity` - namespaced entity class pointed by relation its alias
 * `$type` - relation type, `one` or `many`
 * `$keys` - array containing local fields as keys and referenced fields as corresponding values
 * `$localValue` - array with field value pairs
 * `$referencedValue` - same as above, but for referenced table
 * `$container` - entity field where relation entities exist, if not set - field will be same as entity class without namespace

For example, one `BlogEntry` can contain `Author` entity and many `Comment` entities.
To retrieve them in one operation two relations must be defined: one-to-one for `Author` and one-to-many for `Comment`:

	$AuthorRelation = new Relation('\Author', 'one', array('author_id' => 'id'));
	$CommentRelation = new Relation('\Comment', 'many', array('id' => 'entry_id'), array(), array('visible' => 1), 'Comments');

`Author` will be available in `Author` property.
All `Comment` entities with `visibility` property equal to `1` will be placed in `Comments` property.

**Important**
Relations are unidirectional, therefore if `Author` should point to `BlogEntry`, new relation in `Author` model must be defined.