# MOSS Storage

It is a simple ORM developed for [MOSS framework](https://github.com/Potfur/moss).
In philosophy similar to _data mapper_ pattern, that moves data between objects and database and tries to keep them independent of each other.

In active record pattern, entities (objects with data) often extend some base class.
That base class adds functionality to read/write from database but also bloats entire design... and adds unnecessary tight coupling.

`Storage` approaches this differently. Your entities have no direct connection to database.
Your business logic stays uninfluenced by database (repository).
Write entities as you want, extend what you need, independently from any databases, weird ORM base classes.

Each entity that will be stored in repository, in its own table, must be described by `Model`.
`Model` describes how entities properties relate/are mapped to table cells.
In addition, `Model` stores information about table field types (integer, string or maybe datetime or even serial) and their attributes.

Beside `Model`, `Storage` comes with two important components:
`Schema` that allows creation, alteration and removal of tables in repository.
`Query` is responsible for all CRUD operations, relations and will be most used.