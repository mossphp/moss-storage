# Moss Storage

[![Build Status](https://travis-ci.org/potfur/moss-storage.png?branch=master)](https://travis-ci.org/potfur/moss-storage)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/potfur/moss-storage/badges/quality-score.png?s=6b11b311a9dfe2d150f9b2ac8568426b2ed3bc9f)](https://scrutinizer-ci.com/g/potfur/moss-storage/)
[![Code Coverage](https://scrutinizer-ci.com/g/potfur/moss-storage/badges/coverage.png?s=f1e8ae97cb136068a9592fbb8f694cb392ec2a24)](https://scrutinizer-ci.com/g/potfur/moss-storage/)

**Storage** is a simple ORM developed for [MOSS framework](https://github.com/potfur/moss) as completely independent library. In philosophy similar to _Data Mapper_ pattern that allows moving data from object instances to database, while keeping them independent of each other.

_Active Record_ brakes single responsibility principle (by extending some some base class), bloats entire design... and adds unnecessary coupling.
**Storage** approaches this differently. Entities have no direct connection to database, business logic stays uninfluenced by repositories.
The only connection between entities and database is in **Storage** itself - in models that describe how entities relate to repositories.

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

For licence details see LICENCE.md
Documentation is available in ./docs/

## Requirements

Just PHP >= 5.4, Doctrine DBAL and SQL database.

## Installation

Download from [github](https://github.com/potfur/moss-storage)
Or via [Composer](https://getcomposer.org/)

```json
	"require": {
		"moss/storage": ">=0.9"
	}
```

Storage has no external dependencies.
(Only PHPUnit for developement).

## Contribute

If you want to submit fix or some other enhancements, feel free to do so.
Whenever you find a bug it would be nice if you submit it.
And if you submit fix - this would be truly amazing!

### How to Contribute

 * Fork the **Storage** repository;
 * Create a new branch for each feature/improvement/issue;
 * Send a pull request from branch

### Style Guide

All pull requests must adhere to the PSR-2 standard.
All pull requests must be accompanied by passing PHPUnit tests.
