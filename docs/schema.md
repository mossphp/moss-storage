# Schema

`Schema` responsibility is to create, alter and drop entity containers.

### Check

Checks if data container for entity exists (does not check if is up-to-date)

	$bool = $storage
		->check('entity')
		->execute();

### Create

Creates data container for entity based on its model

	/* CREATE TABLE ... */
	$storage
		->create('entity')
		->execute();

### Alter

Updates existing data container to match current model

	/* ALTER TABLE ... */
	$storage
		->alter('entity')
		->execute();

### Drop

Drops entity container

	/* DROP TABLE IF EXISTS ... */
	$storage
		->drop('entity')
		->execute();