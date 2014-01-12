# MOSS Storage

It is a simple ORM developed for [MOSS framework](https://github.com/Potfur/moss).
In philosophy similar to _data mapper_ pattern, that moves data between objects and database and tries to keep them independent of each other.

In active record pattern, entities (objects with data) often extend some base class.
That base class adds functionality to read/write from database but also bloats entire design... and adds unnecessary tight coupling.

`Storage` approaches this differently. Your entities have no direct connection to database.
Your business logic stays uninfluenced by database (repository).
Write entities as you want, extend what you need, independently from any databases, weird ORM base classes.

Each entity that will be stored in repository, in its own table, must be described by `Model`.
`Model` defines how entities properties relate/are mapped to table cells.
In addition, `Model` stores information about table field types (integer, string or maybe datetime or even serial) and their attributes.

Beside `Model`, `Storage` comes with two important components:
`Schema` that allows creation, alteration and removal of tables in repository.
`Query` is responsible for all CRUD operations, relations and will be most used.

# Example

Below example creates (and drops if exist) two tables, `table` and `other`.
Inserts three entities - one in `table` and two in `other`.
Next, reads them.

	use moss\storage\driver\PDO;
	use moss\storage\builder\mysql\Query as QueryBuilder;
	use moss\storage\builder\mysql\Schema as SchemaBuilder;
	use moss\storage\model\Model;
	use moss\storage\model\definition\field\Field;
	use moss\storage\model\definition\index\Index;
	use moss\storage\model\definition\index\Primary;
	use moss\storage\model\definition\index\Foreign;
	use moss\storage\model\definition\relation\Relation;
	use moss\storage\Storage;

	// storage initialisation
	$storage = new Storage(
        new PDO('mysql', 'database', 'user', 'password'),
        array(
             new QueryBuilder(),
             new SchemaBuilder()
        )
    );

	// table model
    $table = new Model(
        '\stdClass',
        'table',
        array(
             new Field('id', 'integer', array('unsigned', 'auto_increment')),
             new Field('int', 'integer', array('unsigned')),
             new Field('bool', 'boolean', array('default' => 1)),
             new Field('decimal', 'decimal', array('length' => 4, 'precision' => 2)),
             new Field('string', 'string', array('length' => '128', 'null')),
             new Field('datetime', 'datetime'),
             new Field('serial', 'serial'),
        ),
        array(
             new Primary(array('id')),
             new Index('index', array('bool')),
        ),
        array(
             new Relation('\altClass', 'many', array('id' => 'table_id'), 'other')
        )

    );

	// other entity
    class altClass extends stdClass
    {
    }

	// other model
    $other = new Model(
        '\altClass',
        'other',
        array(
             new Field('id', 'integer', array('unsigned', 'auto_increment')),
             new Field('table_id', 'integer', array('unsigned')),
             new Field('string', 'string', array('length' => '128', 'null')),
        ),
        array(
             new Primary(array('id')),
             new Foreign('foreign', array('table_id' => 'id'), 'other'),
        )
    );

	// registering models
    $storage->register('table', $table);
    $storage->register('other', $other);

	// new table entity
    $entity = new \stdClass();
    $entity->id = 1;
    $entity->int = 2;
    $entity->bool = true;
    $entity->decimal = 12.34;
    $entity->string = 'Lorem ipsum dolor omet';
    $entity->datetime = new DateTime();
    $entity->serial = array('a', 'b', 'c');

	// with two other entities
    $entity->other = array(new altClass(), new altClass());
    $entity->other[0]->text = 'foo';
    $entity->other[1]->text = 'bar';

	// dropping tables if exist
    $storage->drop()
            ->execute();

	// creating tables
    $storage->create()
            ->execute();

	// writing entity with relations
    $storage->write($entity)
            ->relation('other')
            ->execute();

	// reading entity with relations
    $r = $storage->read('table')
                 ->relation('other')
                 ->execute();

    var_dump($r);
