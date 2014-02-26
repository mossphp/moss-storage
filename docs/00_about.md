# Storage

`Storage` is a simple ORM developed for [MOSS framework](https://github.com/potfur/moss).
In philosophy similar to _Data Mapper_ pattern, that moves data between objects and database and tries to keep them independent of each other.

In _Active Record_ pattern, entities (objects with data) often extend some base class.
That base class adds functionality to read from / write into database but also bloats entire design... and adds unnecessary tight coupling.

`Storage` approaches this differently. Entities have no direct connection to database, business logic stays uninfluenced by database (repository).
Entities can be written independently from any databases, weird base classes.

For example:

```php
$result = $storage->readOne('article')
	->where('id', 1)
	->with(array('comments', 'tags'))
	->execute();
```

`Storage` will read single `article` that has `id` equal to `1`, with relationship to: `comments` and `tags`.

In fact, `Storage` is just a facade do simplify creation of `Query` or `Schema` instances:
If one of below methods is called, `Storage` will return `Schema` instance which is responsible for creating, altering and dropping data containers (tables):

 * `::check($entityClass = null)`
 * `::create($entityClass = null)`
 * `::alter($entityClass = null)`
 * `::drop($entityClass = null)`

Those will return `Query` used to read, write and delete data from data containers (tables) :

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
