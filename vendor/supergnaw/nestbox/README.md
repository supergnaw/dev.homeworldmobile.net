# Nestbox [WIP]

A interface for databases using PHP Data Objects written to easily fill gaps of niche requirements. This project is
updated as needs arise and is probably a result of NIH syndrome.

## Nestbox "Birds"

*(or sub-classes, or packages, however you want to view them)*

Each bird *(class)* in the "nestbox" serves as a way to add specific functionality to Nestbox.

| Bird                             | Description                                                                                                                   |
|----------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| [Babbler](Babbler/readme.md)     | A flexible content management system designed with basic website/blog editing functioality in mind.                           |
| [Bullfinch](Bullfinch/readme.md) | An interface designed to easily create and deploy a simple message board. *(Not yet complete/available)*                      |
| [Cuckoo](Cuckoo/readme.md)       | MySQL database in-line encryption for data at rest. *(Not yet complete/available)*                                            |
| [Lorikeet](Lorikeet/readme.md)   | An image upload processing and indexing. *(Not yet complete/available)*                                                       |
| [Magpie](Magpie/readme.md)       | A user and group permissions manager.                                                                                         |
| [Myna](Myna/readme.md)           | An API endpoint management system for easy REST API building. *(Not yet complete/available)*                                  |
| [Titmouse](Titmouse/readme.md)   | A user management interface that can register/login/logout users while adhering to standard practicces for password handling. |

### Bird File Structure

For code cleanliness and future development and maintenance, each bird class should have a structure similar to the
following:

1. Magic Methods
    1. `__construct()` will always call `parent::__construct()` before doing anything else
    2. `__invoke` will always call `$this->__construct()` and nothing else
    3. `__destruct()` will always do bird-specific acitons and must call `parent::__destruct()` at the end
2. Table Methods
    1. A main function that calls all table creation function
    2. An individual function to create each unique table
3. Bird Methods
    - The remaining function for which the class will use, organized in a logical flow in order of how they might be
      used in practice

## Basic Usage

The Nestbox class was designed for simplistic usage for database interaction while incorporating best practices for
safely interacting with the database.

```php
use Supergnaw\Nestbox;

$nb = new Nestbox();
$sql = "SELECT * FROM `users`;";

try {
    if ($nb->query_execute($sql)) {
        $users = $nb->results();
    }
} catch (NestboxException $exception ) {
    die ($exception->getMessage());
}
```

### Database Connection Details

Nestbox database connection defaults can be defined using four constants (`example value`):

- `NESTBOX_DB_HOST`: the database host (`localhost`)
- `NESTBOX_DB_USER`: the database username (`root`)
- `NESTBOX_DB_PASS`: the database password ([`correct horse battery staple`](https://xkcd.com/936/))
- `NESTBOX_DB_NAME`: the database name (`nestbox_database`)

If these constants are not defined, Nestbox will attempt to use the parameters passed when a new instance is created, or
an existing instance is envoked. The benefit of being able to pass unique connection parameters to a given instance is
in the edge case where a connection to a separate database might be needed within a given project.

```php
// create a new instance using defined constants
$nb = new \Supergnaw\Nestbox\Nestbox();

// use the existing instance to create a new connection
$nb(host: $host, user: $user, pass: $pass, name: $name);
```

## Basic Queries

Nestbox has three built-in functions designed to interact with a database using some of the most common forms of
database manipulation: `insert()`, `update()`, `delete()`, and `select()`. Their purpose is to simplify the process by
internally
building prepared statements using the provided data. All values are passed as parameters using `:named` placeholders.
Additionally, table and column names are verified against the database schema with any inconsistencies throwing an
exception.

### Insert

```php
insert(string $table, array $params, bool $update = true): int
```

- `$table`: a string designating the table name
- `$params`: an array of ['column' => 'value'] parameters to insert into `$table`
- `$update`: a boolean indicating update on duplicate key; default is true

The return value is `int` type of the number of rows inserted.

### Update

```php
update(string $table, array $params, array $where, string $conjunction = "AND"): int
```

- `$table`: a string designating the table name
- `$params`: an array of ['column' => 'value'] parameters to update in `$table`
- `$where`: an array of ['column' => 'value'] parameters to determine "where" the update will take place,
  e.g. `['user_id' => 123]`
- `$conjunction`: a string indicating how each $where parameter will be joined. The only two supported options
  are: `AND`, `OR`

The return value is `int` type of the number of rows affected.

*Please note that a return value of `0` does not necessarily mean a query failed to execute, rather no values were
changed.*

### Delete

```php
delete(string $table, array $where, string $conjunction = "AND"): int
```

- `$table`: a string designating the table name
- `$where`: an array of ['column' => 'value'] parameters to determine "where" the deletion will take place,
  e.g. `['user_id' => 123]`
- `$conjunction`: a string indicating how each `$where` parameter will be joined. The only two supported options
  are: `AND` and `OR`, case insensitive

The return value is of type `int` containing the number of affected rows.

### Select

```php
select(string $table, array $where = [], string $conjunction = "AND"): array
```

- `$table`: a string desinating the table name
- `$where`: an array of ['column' => 'value'] parameters to determine which rows of matching values to select,
  e.g. `['user_id' => 123]`
- `$conjunction`: a string indicating how each `$where` parameter will be joined. The only two valid options
  are: `AND` and `OR`, case insensitive

## Transactions

Transactions help with data integrity across multiple tables within the database.

```php
transaction( $query, $params, $commit ): bool
```

The following is an example of how a transaction could be implemented and committed.

```php
// prepare queries as needed
$query1 = "INSERT INTO `my_table_1` (`col_1`, `col_2`) VALUES (:val1, :val2);";
$params1 = ["val1" => "foo", "val2" => "bar"];

$query2 = "INSERT INTO `my_table2' (`col_2`, `col_4`) VALUES (:val1, :val2);";
$params2 = ["val1" => "foo", "val2" => "bar"];

// execute and commit queries
$nb->transaction(query: $query1, params: $params1, commit: false);
$nb->transaction(query: $query2, params: $params2, commit: true);
```

## Database Schema

Nestbox has internal functions to validate the database schema of quick queries which can also be used separately when
dynamically building application-specific queries.

```php
valid_schema(string $table, string $column = null): bool
````

* returns `true` if `$table` is a valid table name, otherwise false
* returns `true` if `$table` is a valid table name and contains `$column`, otherwise `false`

```php
valid_table(string $table): bool
````

* returns `true` if `$table` is a valid table name, otherwise `false`

```php
valid_column(string $table, string $column): bool
````

* returns `true` if `$table` is a valid table name and contains `$column`, otherwise `false`

```php
valid_trigger(string $table, string $trigger): bool
````

* returns `true` if `$table` is a valid table name and has a trigger named `$trigger`, otherwise `false`

## Exceptions

***todo: add more documentation***

## Further Reading

Since this was a project designed for learning, here are some great references used during the creation of this project:

- [(The only proper) PDO tutorial](https://phpdelusions.net/pdo)
