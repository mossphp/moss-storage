# Storage

## About

`Storage` is a simple ORM developed for [MOSS framework](https://github.com/potfur/moss) as completely independent library.
In philosophy similar to _Data Mapper_ pattern that allows moving data from object instances to database, while keeping them independent of each other.
_Active Record_ brakes single responsibility principle (by extending some some base class), bloats entire design... and adds unnecessary coupling.

`Storage` approaches this differently. Entities have no direct connection to database, business logic stays uninfluenced by repositories.
The only connection between entities and database is in `Storage` itself - in models that describe how entities relate to repositories.

Two examples (assuming that corresponding model exists):

```php
$article = $storage->readOne('article')
	->where('id', 123)
	->with('comment', array(array('visible' => true)))
	->execute();
```
This will read article entity with `id=123` and with all its visible comments.


```php
$obj = new Article('title', 'text');
$obj->comments = array(
	new Comment('It\'s so simple!', 'comment_author@mail'),
	new Comment('Yup, it is.', 'different_author@mail'),
);

$storage->write($obj)->with('comment')->execute();
```
This would write article entity into database with set comments.

## Requirements

Just PHP >= 5.3.7, PDO with MySQL or PostgreSQL and that's it.
(Other SQL engines will come later)

## Installation

Download from [github](https://github.com/potfur/moss-storage)
(Composer will be added shortly)

Storage has no external dependencies.
(Only PHPUnit for developement).

## Operations

`Storage` is represented two classes:

 * `StorageQuery` that will preform all those boring inserts or updates and nice and simple data retrieval,
	* `::num($entityClass)`
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
