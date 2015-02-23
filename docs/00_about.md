# Storage

## About

**Storage** is a simple ORM developed for [MOSS framework](https://github.com/potfur/moss) and works on top op _Doctrines DBAL_.
In philosophy similar to _Data Mapper_ pattern that allows moving data from object instances to database, while keeping them independent of each other.

_Active Record_ brakes single responsibility principle (by extending some some base class), bloats entire design... and adds unnecessary coupling.
**Storage** approaches this differently. Entities have no direct connection to database, business logic stays uninfluenced by repositories.
The only connection between entities and database is in **Storage** itself - in models that describe how entities relate to repositories.

## Requirements

Just PHP >= 5.4 and [Doctrine DBAL](http://www.doctrine-project.org/projects/dbal.html)

## Installation

Download from [github](https://github.com/potfur/moss-storage)
Or via [Composer](https://getcomposer.org/)

```
composer require "moss/storage"
```

```json
	"require": {
		"moss/storage": ">=0.9"
	}
```

## Example

Connect to database:

```php
$conn = DriverManager::getConnection([
    'dbname' => 'test',
    'user' => 'user',
    'password' => 'password',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
    'charset' => 'utf8'
]);
```


Define models - example contains blog `Post` and `Comments`:

```php
$models = new ModelBag();
$models->set(
    new Moss\Storage\Model\Model(
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
    ),
    'post'
);
$models->set(
    new Model(
        '\Comment',
        'comment',
        [
            new Field('id', 'integer', ['autoincrement']),
            new Field('post_id', 'integer', []),
            new Field('created', 'datetime'),
            new Field('body', 'string', []),
        ],
        [
            new Primary(['id']),
            new Index('post_id', ['post_id']),
            new Index('created', ['created']),
            new Foreign('comment_post_fk', ['post_id' => 'id'], 'post'),
        ],
        [
            new One('post', ['post_id' => 'id']),
        ]
    ),
    'comment'
);
```

Create schema:

```php
$schema = new Schema($conn, $models);
$schema->create()->execute();
```

Write some entities into database:

```php
class Post { }
class Comment { }

$comment1 = new Comment();
$comment1->body = 'Comment #1';
$comment1->created = new \DateTime();

$comment2 = new Comment();
$comment2->body = 'Comment #2';
$comment2->created = new \DateTime();

$post = new Post();
$post->title = 'Demo post';
$post->body = 'Demo post body';
$post->created = new \DateTime();
$post->comment = array($comment1, $comment2);

$query = new Query($conn, $models);

$entity = $query->write('post', $post)->with('comment')->execute();
```

And now read it from database:

```php
$result = $query->read('post')->with('comment')->execute();
```
