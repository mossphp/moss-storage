# Storage

## About

`Storage` is a simple ORM developed for [MOSS framework](https://github.com/potfur/moss).
In philosophy similar to _Data Mapper_ pattern, that moves data between objects and database and tries to keep them independent of each other.

_Active Record_ brakes single responsibility principle. Entities in _Active Record_ often extend some base class.
That base class adds functionality to read from / write into database but also bloats entire design... and adds unnecessary tight coupling.

`Storage` approaches this differently. Entities have no direct connection to database, business logic stays uninfluenced by database (repository).
Entities can be written independently from any databases or other weird base classes.
The only connection between entities and database is in `Storage` itself.

For example:

```php
$result = $storage->readOne('article')
	->where('id', 1)
	->with(array('comments', 'tags'))
	->execute();
```

This simple code, will read one `article` with `id = 1` with any comments and tags.

## Requirements

Just PHP >= 5.3.7 and that's it.

## Installation

Download from [github](https://github.com/potfur/moss-storage)

Storage has no external dependencies.
(Only PHPUnit for dev).

## Usage

```php
use Moss\Storage\Driver\PDO;
use Moss\Storage\Builder\MySQL\QueryBuilder;
use Moss\Storage\Builder\MySQL\SchemaBuilder;
use Moss\Storage\Model\Model;
use Moss\Storage\Model\Definition\Field\Integer;
use Moss\Storage\Model\Definition\Field\String;
use Moss\Storage\Model\Definition\Index\Primary;
use Moss\Storage\Storage;

$dsn = sprintf('%s:dbname=%s;host=%s;port=%u', 'mysql', 'database', 'localhost', 3306);
$driver = new Driver\PDO($dsn, 'user', 'password');

$builders = array(
    new QueryBuilder(), // required only for query operations
    new SchemaBuilder() // required only for schema operations
);

$storage = new Storage($driver, $builders);

$articleModel = new Model(
    '\stdClass',
    'article',
    array(
        new Integer('id', array('unsigned', 'auto_increment')),
        new String('text'),
    ),
    array(
        new Primary(array('id')),
    )
);

$storage->register($articleModel, 'article');
$storage->create()
	->execute();

$article = new \stdClass();
$article->text = 'Lorem ipsum';
$storage->insert($article)
	->execute();

$articles = $storage->read('article')
	->execute();

var_dump($articles);
```

## Operations

In fact, `Storage` is just a proxy do simplify creation of `Query` (read, write and delete data from tables) or `Schema` (responsible for creating, altering and dropping tables) instances.

If one of below methods is called, `Storage` will return `Schema` instance, configured for set operation and entity type:

 * `::check($entityClass = null)`
 * `::create($entityClass = null)`
 * `::alter($entityClass = null)`
 * `::drop($entityClass = null)`

Those will return `Query` instance, also ready to use:

 * `::count($entityClass)`
 * `::readOne($entityClass)`
 * `::read($entityClass)`
 * `::insert($entity)`
 * `::write($entity)`
 * `::update($entity)`
 * `::delete($entity)`
 * `::clear($entityClass)`

But before `Storage` can do its work, each entity type that will be stored in repository, must be described by `Model`.
`Model` defines how entity properties relate/are mapped to table cells. and information about field types (integer, string or maybe datetime or even serial) and their attributes
In addition, `Model` can store indexes, primary and foreign keys and relations between other entities.
