# Schema

`Schema` responsibility is to create, alter and drop entity tables.

### Check

Checks if table for entity exists (does not check if is up-to-date)

	$bool = $storage
		->check('entity')
		->execute();

### Create

Creates table for entity based on its model

	/* CREATE TABLE ... */
	$storage
		->create('entity')
		->execute();

### Alter

Updates existing table to match current model

	/* ALTER TABLE ... */
	$storage
		->alter('entity')
		->execute();

### Drop

Drops entity table

	/* DROP TABLE IF EXISTS ... */
	$storage
		->drop('entity')
		->execute();