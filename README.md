# Moss Storage

[![Build Status](https://travis-ci.org/potfur/moss-storage.png?branch=master)](https://travis-ci.org/potfur/moss-storage)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/potfur/moss-storage/badges/quality-score.png?s=6b11b311a9dfe2d150f9b2ac8568426b2ed3bc9f)](https://scrutinizer-ci.com/g/potfur/moss-storage/)
[![Code Coverage](https://scrutinizer-ci.com/g/potfur/moss-storage/badges/coverage.png?s=f1e8ae97cb136068a9592fbb8f694cb392ec2a24)](https://scrutinizer-ci.com/g/potfur/moss-storage/)

`Storage` is a simple ORM developed for [MOSS framework](https://github.com/potfur/moss) but can be used in other projects.
In philosophy similar to _Data Mapper_ pattern, that moves data between objects and database and tries to keep them independent of each other.

_Active Record_ brakes single responsibility principle. Entities in _Active Record_ often extend some base class.
That base class adds functionality to read from / write into database but also bloats entire design... and adds unnecessary tight coupling.

`Storage` approaches this differently. Entities have no direct connection to database, business logic stays uninfluenced by database (repository).
Entities can be written independently from any databases or other weird base classes.
The only connection between entities and database is in `Storage` itself.

```php
$result = $storage->readOne('article')
	->where('id', 1)
	->with(array('comments', 'tags'))
	->execute();
```

This simple code, will read one `article` with `id = 1` with any comments and tags.

For licence details see LICENCE.md
Documentation is available in ./docs/

## Contribute

If you want to submit fix or some other enhancements, feel free to do so.
Whenever you find a bug it would be nice if you submit it.
And if you submit fix - this would be truly amazing!

### How to Contribute

 * Fork the `Storage` repository;
 * Create a new branch for each feature/improvement/issue;
 * Send a pull request from branch

### Style Guide

All pull requests must adhere to the PSR-2 standard.
All pull requests must be accompanied by passing PHPUnit tests.