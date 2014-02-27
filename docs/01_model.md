# Model

`Model` represents entity structure, how objects properties are mapped to table fields, what indexes exist and how modeled entity relate to other.

Each `Model` consists of:

 * namespaced entity class name
 * table name
 * any number of field definitions
 * any number of index definitions
 * any number of relation definitions

To create model type:

```php
$model = new \Moss\Storage\Model\Model(
	'\some\Entity',
	'someTable',
	array(...), // array containing field definitions
	array(...), // array containing index definitions
	array(...) // another array with relation definitions
)
```

Each entity class must have separate model which must be registered in `Storage`:

```php
$storage = new Storage($Adapter);
$storage->register($SomeModel);
```

When creating query you can call entity by namespaced class name or by alias, as in example below:

```php
$storage->register($SomeModel, 'someAlias');
```

## Fields

Object mapping means that entity properties are represented as table fields.
Therefore `Model` must contain at least one field definition:

```php
$field = new Field($field, $type, $attributes, $mapping);
```

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

For example - decimal field, with 4 digits, 2 of which on the fractional, entity property is called `fooBar` but stored in `foo_bar` field:

```php
$field = new Field('fooBar', 'decimal', array('null', 'length' => 4, 'precision' => 2), 'foo_bar');
```

## Indexes & Keys

Model may contain index or key definitions

### Primary key

As primary keys name is often reserved, there is no need to type its name, just define columns used in key

```php
$primary = new Primary(array('id'));
```

### Foreign key

Foreign key definition must include its name - unique within entire database, array containing fields from local and foreign table - as key-value pairs and foreign table name.

```php
$foreign = new Foreign('fk_other_id', array('other_id' => 'id'), 'other');
```

Above definition says that foreign key `fk_other_id` will constrain field `other_id` to values from `other.id`.

### Unique

To define unique index just type its name and array with columns

```php
$unique = new Unique('uk_id', array('id'));
```

### Index

Index definition consists from its name and column names

```php
$index = new Index('i_id', array('id'));
```

## Relations

Relations describe what other entities can be contained inside entity.
`Storage` supports only two relations:

 * `one` - one-to-one relation, where entity points to exactly one entity,
 * `many` - one-to-many relation, entity points to many entities,
 * `oneTrough` - one-to-one relation with mediator table in between,
 * `manyTrough` - many-to-many relation with mediator table in between

Both `one` and `many` relations are defined as:

```php
$relation = new Relation($entity, $type, $keys, $container);
```

 * `$entity` - namespaced entity class pointed by relation its alias
 * `$type` - relation type, `one` or `many`
 * `$keys` - array containing local fields as keys and referenced fields as corresponding values
 * `$container` - entity field where relation entities exist, if not set - field will be same as entity class without namespace

For example, one `BlogEntry` can contain `Author` entity and many `Comment` entities.
To retrieve them in one operation two relations must be defined: one-to-one for `Author` and one-to-many for `Comment`, `Author` will be available in `Author` property.:

```php
$authorRelation = new Relation('\Author', 'one', array('author_id' => 'id'));
```

Relations with mediator table - `oneTrough` and `manyTrough` are defined as:

```php
$relation = new Relation($entity, $type, array($localKeys, $foreignKeys), $container, $mediator);
```

 * `$entity` - namespaced entity class pointed by relation its alias
 * `$type` - relation type, `one` or `many`
 * `$localKeys` - array containing entity fields as keys and mediator fields as values
 * `$foreignKeys` - array containing mediator fields as keys and referenced entity fields as values
 * `$container` - entity field where relation entities exist, if not set - field will be same as entity class without namespace
 * `mediator` - name of model representing mediator table

**Important**
Relations are unidirectional, therefore if `Author` should point to `BlogEntry`, new relation in `Author` model must be defined.