# Storage

## About

`Storage` is a simple ORM developed for [MOSS framework](https://github.com/potfur/moss) as completely independent library.
In philosophy similar to _Data Mapper_ pattern - to allow moving data from object instances to database, while keeping them independent of each other.

_Active Record_ brakes single responsibility principle (by extending some some base class), bloats entire design... and adds unnecessary coupling.

`Storage` approaches this differently. Entities have no direct connection to database, business logic stays uninfluenced by database (repository).
Entities can be written independently from any databases or other weird base classes.
The only connection between entities and database is in `Storage` itself.

For example (assuming that corresponding model exists):

```php
$obj = new \stdClass();
$obj->text = 'Some text';

$storage->write($obj)->execute();
```

This would write entity into database.

## Requirements

Just PHP >= 5.3.7 and that's it.

## Installation

Download from [github](https://github.com/potfur/moss-storage)

Storage has no external dependencies.
(Only PHPUnit for dev).

## Operations

`Storage` is represented two classes:

 * `StorageQuery` that will preform all those boring inserts or updates and nice and simple data retrieval,
	* `::count($entityClass)`
	* `::readOne($entityClass)`
	* `::read($entityClass)`
	* `::insert($entity)`
	* `::write($entity)`
	* `::update($entity)`
	* `::delete($entity)`
	* `::clear($entityClass)`
 * `StorageSchema` responsible for creating, altering and dropping tables,
	* `::check($entityClass = null)`
	* `::create($entityClass = null)`
	* `::alter($entityClass = null)`
	* `::drop($entityClass = null)`

But before any of those classes can do its work, each entity type that will be stored in repository, must be described by `Model`.
`Model` defines how entity properties relate/are mapped to table cells, their types (integer, string or maybe datetime or even serial) and other attributes
In addition, `Model` can store indexes, primary and foreign keys and how entities relate to each other.
