# Storage

`Storage` is a simple ORM developed for [MOSS framework](https://github.com/Potfur/moss).
In philosophy similar to _Data Mapper_ pattern, that moves data between objects and database and tries to keep them independent of each other.

In Active Record pattern, entities (objects with data) often extend some base class.
That base class adds functionality to read from / write into database but also bloats entire design... and adds unnecessary tight coupling.

`Storage` approaches this differently. Your entities have no direct connection to database.
Your business logic stays uninfluenced by database (repository).
Write entities as you want, extend what you need, independently from any databases, weird base classes.

`Storage` is just a facade with set of methods simplifying instance creation.
Most of its methods will create `Query` or `Schema` instance.
`Query` is responsible for all inserts, updates and deletions, but mostly will be used to read data - entities with or without use of relations.
`Schema` allows for creation, alteration and tables removal.

Those four methods will return `Schema` instance:

 * `::check($entityClass = null)`
 * `::create($entityClass = null)`
 * `::alter($entityClass = null)`
 * `::drop($entityClass = null)`

Those will return `Query`:

 * `::count($entityClass)`
 * `::readOne($entityClass)`
 * `::read($entityClass)`
 * `::insert($entity)`
 * `::write($entity)`
 * `::update($entity)`
 * `::delete($entity)`
 * `::clear($entityClass)`

But before `Query` or `Schema` can do their work, each entity type that will be stored in repository, must be described by `Model`.
`Model` defines how entities properties relate/are mapped to table cells.
In addition, `Model` stores information about table field types (integer, string or maybe datetime or even serial) and their attributes.

## Usage

To work `Storage` needs instances of following interfaces:

 * `\moss\storage\driver\DriverInterface` - required to connect to database
 * `\moss\storage\builder\QueryInterface` - required to build data queries
 * `\moss\storage\builder\SchemaInterface` - required to manage tables and their structure

Currently only `PDO` and `MySQL` implementations are available.
Instantiation will look like this:

```php
$storage = new \moss\storage\Storage(
    new \moss\storage\driver\PDO('mysql', 'test', 'user', ''),
    array(
         new \moss\storage\builder\mysql\Query(),
         new \moss\storage\builder\mysql\Schema()
    )
);
```

After that just register some models:

```php
$storage->register('table', $table);
```