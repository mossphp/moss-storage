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
use Moss\Storage\Model\Model;

$model = new Model(
	'\some\Entity',
	'someTable',
	array(...), // array containing field definitions
	array(...), // array containing index definitions
	array(...) // another array with relation definitions
)
```

Each entity class must have separate model which must be registered in `Storage`:
When creating query you can call entity by namespaced class name or by alias, as in example below:

```php
$storage->register($someNamespacedClassModel, 'someAlias');

$result = $storage->read('\some\namespaced\Class')->execute();
$result = $storage->read('someAlias')->execute();
```

## Fields

Object mapping means that entity properties are represented as table fields.
Therefore `Model` must contain at least one field definition.

```php
use Moss\Storage\Model\Definition\Field\Integer;

$field = new Integer($name, $attributes, $mapping);
```

Each field definition consists of its name (`$name`), array with attributes (`$attribute`) and mapping.
Only name is required.

Attributes array is different for each field, eg only `integer` fields can have `auto_increment` attribute, but `integers` do not support `precision`.

Parameter `$mapping` is provided for situations where field name is different than property name.
For instance property `companyAddress` can be represented as `company_address` field.

Field types:
  * `Boolean` - boolean field (attributes: `null`, `default`)
  * `Integer` - signed integer (attributes: `length`, `null`, `auto_increment`, `default`)
  * `Decimal` - decimal field (attributes: `length`, `precision`, `null`, `default`)
  * `String` - string, text (attributes: `length`, `null`)
  * `DateTime` - datatime field, can convert itself from and into `\DateTime` instance (attributes: `null`, `default`)
  * `Serial` - serialized data: array, object or maybe file (attributes: `null`)

Supported attributes:

  * `null` - marks field as nullable
  * `default` - sets default value
  * `auto_increment` - field is auto incremented (only `Integer`)
  * `length` - field length
  * `precision` - field precision (used only in `Decimal`)

For example - decimal field, with 4 digits, 2 of which on the fractional, can be null, entity property is called `fooBar` but stored in `foo_bar` field:

```php
use Moss\Storage\Model\Definition\Field\Decimal;

$field = new Decimal('fooBar', array('null', 'length' => 4, 'precision' => 2), 'foo_bar');
```

## Indexes, Constraints & Keys

Model may contain index or key definitions

### Primary key

As primary keys name is often reserved, there is no need to type its name, just define columns used in key

```php
use Moss\Storage\Model\Definition\Index\Primary;

$primary = new Primary(array('id'));
```

### Foreign key

Foreign key definition must include its name, array containing fields from local and foreign table - as key-value pairs and foreign table name.

```php
use Moss\Storage\Model\Definition\Index\Foreign;

$foreign = new Foreign('fk_other_id', array('other_id' => 'id'), 'other');
```

Above definition says that foreign key `fk_other_id` will constrain field `other_id` to values from `other.id`.

### Unique

To define unique constraint just type its name and array with columns

```php
use Moss\Storage\Model\Definition\Index\Unique;

$unique = new Unique('uk_id', array('id'));
```

### Index

Index definition consists from its name and column names

```php
use Moss\Storage\Model\Definition\Index\Index;

$index = new Index('i_id', array('id'));
```

## Relations

Relations describe what other entities can be contained inside entity.
So, `Storage` provides with basic `one-to-one` and `one-to-many` relations.

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

And more complicated `many-to-many` relation. Also `Storage` supports `one-to-one with mediator/pivot` relation.

```php
use Moss\Storage\Model\Definition\Relation\OneTrough;
use Moss\Storage\Model\Definition\Relation\ManyTrough;

$rel = new OneTrough($entity, $in, $out, $mediator, $container);
$rel = new ManyTrough($entity, $in, $out, $mediator, $container);
```

`$entity` and `$container` are same as in previous relations. Main difference is in defining keys.
Both relations require some kind of mediator/pivot table, therefore `$in` and `$out` keys need to be defined.
Also, `$mediator` must point to mediator/pivot table that binds both entities.

**Important**
Table acting as mediator/pivot must be described by `Model` as any other table, but does not require class representing entity.
In such cache, while defining `Model` its class name can be omitted wit `null` value.
Results from such table, will be represented as associative arrays (if there should be any need for them).

For example, one `BlogEntry` can contain `Author` entity and many `Comment` entities.
To retrieve them in one operation two relations must be defined: one-to-one for `Author` and one-to-many for `Comment`, `Author` will be available in `Author` property.:

```php
$commentRelation = new Many('\cms\Comment', array('id' => 'article_id'));
$authorRelation = new One('\cms\Author', array('author_id' => 'id'));
```

If `BlogEntry` should have tags:

```php
$tagRelation = new ManyTrough('\cms\Tag', array('id' => 'article_id'), array('tag_id' => 'id'), 'article_tag`);
```

**Important**
Relation definitions are unidirectional, therefore if `Author` should point to `BlogEntry`, new relation in `Author` model must be defined.