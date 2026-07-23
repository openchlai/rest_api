# REST_API

This is a 'No code' library that sits in front of your mysql tables and generates REST APIs 'on the fly'.

- List     GET /
- View     GET /:id
- Add:	   POST
- Update   POST /:id

The library automaticaly detects your mysql table's schema and automaticaly creates the REST API endpoint.

The REST API functionality can be extended with config files. 

Each config file abstracts concepts that are NOT captured in a mysql table schema.

FEATURES IN MYSQL TABLE SCHEMA
- Column Name
- Data Type
- Minimum and Maximum length
- Mandatory fields
- Foreign Keys

FEATURES IN CONFIG FILE
- Column name alias: api field name is different from table field name
- handling of hierachical values  eg location, categories
- aggregate column values eg total price, etc
- multi-table transactions

# EXAMPLE
See examples/index.php
