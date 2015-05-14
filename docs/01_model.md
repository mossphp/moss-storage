# Model

`Model` represents entity structure, how objects properties are mapped to table fields, what indexes exist and how modeled entity relate to other.

Each `Model` consists of:

 * namespaced entity class name
 * table name
 * one or more field definitions
 * any number of index definitions
 * any number of relation definitions

To create model type:

```php
use Moss\Storage\Model\Model;

$model = new Moss\Storage\Model\Model(
	'\Post',
	'post',
	[
		new Field('id', 'integer', ['autoincrement']),
		new Field('created', 'datetime'),
		new Field('title', 'string', ['length' => 128]),
		new Field('body', 'string', []),
	],
	[
		new Primary(['id']),
		new Index('created', ['created']),
	],  
	[
		new Many('comment', ['id' => 'post_id']),
	]
);
```

Each entity class must have separate model which must be registered in `ModelBag`:
When creating query you can call entity by namespaced class name or by provided alias, as in example below:

```php
$model = new Moss\Storage\Model\Model('\some\Class', ... );
$bag->register($model, 'someAlias');

$result = $storage->read('\some\Class')->execute();
$result = $storage->read('someAlias')->execute();
```

## Fields

Object mapping means that entity properties are represented as table fields.
`Model` must contain at least one field which is represented by instance of `Field`.

```php
$field = new Field('title', 'text', []); 
```

**Storage** allows for all field types allowed by _Doctrines DBAL_:

 * Integer types
    * `smallint`
    * `integer`
    * `bigint`
 * Decimal types
    * `decimal`
    * `float`
 * Character string types
    * `string`
    * `text`
    * `guid`
 * Binary string types
    * `binary`
    * `blob`
 * Bit types
    * `boolean`
 * Date and time types
    * `date`
    * `datetime`
    * `datetimetz`
    * `time`
 * Array types
    * `array`
    * `simple_array`
    * `json_array`
 * Object types
    * `object`

And should handle any custom type that DBAL can.

## Indexes, Constraints & Keys

`Model` may contain index or key definitions.
They are not needed for mapping but since model can be used (and is) to create schema, why not do it with keys and constraints.

### Primary key

Primary key consists of array of columns.
As primary keys name is often reserved, there is no need to type its name.

```php
use Moss\Storage\Model\Definition\Index\Primary;

$primary = new Primary(['id']);
```

### Foreign key

Foreign key definition must include its name, array containing fields from local and foreign table - as key-value pairs and foreign table name.

```php
use Moss\Storage\Model\Definition\Index\Foreign;

$foreign = new Foreign('fk_other_id', ['other_id' => 'id'], 'other');
```

Above definition says that foreign key `fk_other_id` will constrain field `other_id` to values from field `id` from table `other`.

### Unique

To define unique constraint just type its name and array with columns

```php
use Moss\Storage\Model\Definition\Index\Unique;

$unique = new Unique('uk_id', ['id']);
```

### Index

Index definition consists from its name and column names

```php
use Moss\Storage\Model\Definition\Index\Index;

$index = new Index('i_id', ['id']);
```

## Relations

Relations describe what other entities can be contained inside entity.
So, **Storage** provides with basic `one-to-one` and `one-to-many` relations.

```php
use Moss\Storage\Model\Definition\Relation\One;
use Moss\Storage\Model\Definition\Relation\Many;

$rel = new One($entity, $keys, $container);
$rel = new Many($entity, $keys, $container);
```

Where:

 * `$entity` - referenced model by its entity class name or alias under which it was registered
 * `$keys` - array containing local fields as keys and referenced fields as corresponding values
 * `$container` - entity field where relation entities exist, if not set - field will be same as entity class without namespace


**Example**
Book has one author and relation between book and author can be represented this way:
```php
new One('author', ['author_id' => 'id'])
```

Author wrote many books:
```php
new Many('book', ['id' => 'author_id']),
```

And more complicated `many-to-many` and `one-to-one` (trough mediator/pivot table)` relation.

```php
use Moss\Storage\Model\Definition\Relation\OneTrough;
use Moss\Storage\Model\Definition\Relation\ManyTrough;

$rel = new OneTrough($entity, $in, $out, $mediator, $container);
$rel = new ManyTrough($entity, $in, $out, $mediator, $container);
```

`$entity` and `$container` have same meaning as in previous relations. Main difference is in defining keys.
Both relations require some kind of mediator/pivot table, therefore `$in` and `$out` keys need to be defined.
Also, `$mediator` must point to mediator/pivot table that binds both entities.

**Example**
Same case as in simple relations, but this time with mediator/pivot table:
```php
new OneTrough('author', ['id' => 'book_id'], ['author_id' => 'id'], 'book_author')
```

Author wrote many books:
```php
new ManyTrough('book', ['id' => 'author_id'], ['book_id' => 'id'], 'book_author')
```

**Important**
Table acting as mediator/pivot must be described by `Model` as any other table, but does not require class representing entity.
In such cache, while defining `Model` its class name can be omitted wit `null` value.
Results from such table, will be represented as associative arrays (if there should be any need for them).

**Important**
Relation definitions are unidirectional, therefore if `Author` should point to `BlogEntry`, new relation in `Author` model must be defined.
