# Working with Models
A model represents the information (data) of the application and the rules to manipulate that data. Models are primarily used for managing
the rules of interaction with a corresponding database table. In most cases, each table in your database will correspond to one model in
your application. The bulk of your application's business logic will be concentrated in the models.

`ManaPHP\\Mvc\\Model` is the base for all models in a ManaPHP application. It provides database independence, basic
CRUD functionality, advanced finding capabilities, among other services.
`ManaPHP\\Mvc\\Model` avoids the need of having to use SQL statements because it translates
methods dynamically to the respective database engine operations.

> Models are intended to work on a database high layer of abstraction. If you need to work with databases at a lower level check out the [db component](db.html) documentation.

## Creating Models
A model is a class that extends from `ManaPHP\\Mvc\\Model`. It should be placed in the `Models` directory. A model
file must contain a single class; its class name should be in [camel case](https://en.wikipedia.org/wiki/CamelCase) notation:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public $city_id;
        public $city_name;
        public $country_id;
        public $created_time;
    }
```

The above example shows the implementation of the "City" model. Note that the class City inherits from `ManaPHP\\Mvc\\Model`.
This component provides a great deal of functionality to models that inherit it, including basic database
CRUD (Create, Read, Update, Delete) operations, as well as sophisticated search support.

### Mapping of model to table
By default, the model "City" will refer to the table "city". If you want to manually specify other name for the mapping table,
you can use the `getSource()` method:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        //...

        public function getSource()
        {
            return 'the_city';
        }
    }
```

The model City now maps to "the_city" table.
The `initialize()` method aids in setting up the model with a custom behavior i.e. a different table.
The `initialize()` method is only called once during the request.

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
       //...

        public function initialize()
        {
            $this->setSource('the_city');
        }
    }
```

The `initialize()` method is only called once during the request, it's intended to perform initializations that apply for
all instances of the model created within the application. If you want to perform initialization tasks for every instance
created you can `onConstruct`:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        //...

        public function onConstruct()
        {
            // ...
        }
    }
```

### Public properties

Models can be implemented with properties of public scope, meaning that each property can be read/updated
from any part of the code that has instantiated that model class without any restrictions:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public $city_id;

        public $city_name;

        public $country_id;
    }
```

Public properties provide less complexity in development.

### Models in Namespaces

[Namespaces](http://php.net/manual/en/language.namespaces.rationale.php) can be used to avoid class name collision. The mapped table is taken from the class name, in this case 'City':

```php
    <?php

    namespace Application\Home\Models;

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        // ...
    }
```

## Understanding Records To Objects
Every instance of a model represents a row in the table. You can easily access record data by reading object properties. For example,
for a table "city" with the records:

```bash
    mysql> SELECT * FROM city;

    +---------+------------+------------+
    | city_id | city_name  | country_id |
    +---------+------------+------------+
    |  1      | Rob        | 76         |
    |  2      | Aden       | 25         |
    |  3      | Ado        | 16         |
    +---------+------------+------------+

    3 rows in set (0.00 sec)
```

You could find a certain record by its primary key and then print its name:

```php
    <?php

    // Find record with city_id = 3
    $city = City::findFirst(3);

    // Prints "Ado"
    echo $city->city_name;
```

Once the record is in memory, you can make modifications to its data and then save changes:

```php
    <?php

    $city = City::findFirst(3);
    $city->city_name = 'Beijing';
    $city->update();
```

As you can see, there is no need to use raw SQL statements. `ManaPHP\\Mvc\\Model` provides high database
abstraction for web applications.

## Finding Records
`ManaPHP\\Mvc\\Model` also offers several methods for querying records. The following examples will show you
how to query one or more records from a model:

```php
    <?php

    // How many cities are there?
    $cities = City::find();
    echo 'There are ', count($cities), "\n";

    // How many cities of country_id is equal to 1 are there?
    $cities = City::find(['country_id'=>1]);
    echo 'There are ', count($cities), "\n";

    // Get and print cities where country_id is equal to 1 ordered by city_name
    $cities = City::find([
            ['country_id'=>1],
            'order' => 'city_name ASC'
        ]);
    foreach ($cities as $city) {
        echo $city->city_name, "\n";
    }

    // Get first 100 cities ordered by city_name ASC
    $cities = City::find(
        array(
            '',//any record
            'order' => 'city_name',
            'limit' => 100
        )
    );
    foreach ($cities as $city) {
       echo $city->city_name, "\n";
    }
```

You could also use the `findFirst()` method to get only the first record matching the given criteria:

```php
    <?php

    // What's the first city in city table?
    $city = City::findFirst();
    echo 'The city name is ', $city->city_name, "\n";

    // What's the first city of country_id is equal to 1 in city table?
    $city = City::findFirst(['country_id'=>1]);
    echo 'The first city name is ', $city->city_name, "\n";

    // Get first city ordered by city_name
    $city = City::findFirst(
        array(
            '',//any record
            'order' => 'city_name'
        )
    );
    echo 'The first city name is ', $city->city_name, "\n";
```

Both `find()` and `findFirst()` methods accept an associative array specifying the search criteria:

```php
    <?php

    $city = City::findFirst([
            ['country_id' => 10],
            'order' => 'name DESC',
            'limit' => 30
        ]);

    $city = City::find([
            'conditions' => 'country_id = :city_id',
            'bind'       => ['city_id' => 10]
        ]);
```
The available query options are:

| Parameter   | Description                                                                                                                                                                                                                          | Example                                                                   |
|-------------|:-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:--------------------------------------------------------------------------|
| conditions  | Search conditions for the find operation. Is used to extract only those records that fulfill a specified criterion. By default `ManaPHP\\Mvc\\Model` assumes the first parameter are the conditions. | `"conditions" => "name LIKE 'steve%'"`                                                                    |
| columns     | Return specific columns instead of the full columns in the model. When using this option an incomplete object is returned                                                                                                            | `"columns" => "id, name"`                                                 |
| bind        | Bind is used together with options, by replacing placeholders and escaping values thus increasing security                                                                                                                           | `"bind" => ["status" => "A", "type" => "some-time"]`                      |
| order       | Is used to sort the resultset. Use one or more fields separated by commas.                                                                                                                                                           | `"order" => "name DESC, status"`                                          |
| limit       | Limit the results of the query to results to certain range                                                                                                                                                                           | `"limit" => 10`                                                           |
| offset      | Offset the results of the query by a certain amount                                                                                                                                                                                  | `"offset" => 5`                                                           |
| group       | Allows to collect data across multiple records and group the results by one or more columns                                                                                                                                          | `"group" => "name, status"`                                               |

If you prefer, there is also available a way to create queries in an object-oriented way, `\ManaPHP\Mvc\Model\QueryBuilder `instead of using an array of parameters,which
is friendly with IDE auto completes :

```php
    <?php

    $builder=new ManaPHP\Mvc\Model\QueryBuilder();
    $builder->columns('*')
            ->addFrom(City::class)
            ->where('city_id >=:city_id',['city_id'=>10])
            ->orderBy('city_id DESC')
            ->getQuery()->execute();
```

All the `ManaPHP\\Mvc\\Model` queries are internally handled by `\\ManaPHP\\Mvc\\Model\\QueryBuilder` object.

### Model Resultset

While `findFirst()` returns directly an instance of the called class (when there is data to be returned), the `find()` method returns an
array, everyone is an instance of the called class (when there is data to be returned).

```php
    <?php

    // Get all cities
    $cities = City::find();

    // Traversing with a foreach
    foreach ($cities as $city) {
        echo $city->city_name, "\n";
    }

    // Count the resultset
    echo count($cities);

    // Access a city by its position in the resultset
    $city = $cities[5];

    // Check if there is a record in certain position
    if (isset($cities[3])) {
       $city = $cities[3];
    }
```

### Binding Parameters

[Bound parameters](http://php.net/manual/en/pdo.prepared-statements.php) are also supported in `ManaPHP\\Mvc\\Model`. You are encouraged to use
this methodology so as to eliminate the possibility of your code being subject to [SQL injection](https://en.wikipedia.org/wiki/SQL_injection) attacks.
Only string placeholders are supported. Binding parameters can simply be achieved as follows:

```php
    <?php

    // Query cities by binding parameters with string placeholders
    $conditions = 'city_name = :city_name';

    // Parameters whose keys are the same as placeholders
    $parameters = [
        'city_name' => 'Rob',
    ];

    // Perform the query
    $cities = City::find(
        array(
            $conditions,
            'bind' => $parameters
        )
    );
```

Strings are automatically escaped using [PDO]. This function takes into account the connection charset, so its recommended to define
the correct charset in the connection parameters or in the database configuration, as a wrong charset will produce undesired effects
when storing or retrieving data.


> Bound parameters are available for all query methods such as `find()` and `findFirst()` but also the calculation methods like `count()`, `sum()`, `average()` etc.

### Initializing/Preparing fetched records

May be the case that after obtaining a record from the database is necessary to initialise the data before
being used by the rest of the application. You can implement the method `afterFetch` in a model, this event
will be executed just after create the instance and assign the data to it:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public $city_id;

        public $city_name;

        public $status;

        public function beforeSave()
        {
            // Convert the array into a string
            $this->status = implode(',', $this->status);
        }

        public function afterFetch()
        {
            // Convert the string to an array
            $this->status = explode(',', $this->status);
        }
        
        public function afterSave()
        {
            // Convert the string to an array
            $this->status = explode(',', $this->status);
        }
    }
```

## Generating Calculations
Calculations (or aggregations) are helpers for commonly used functions of database systems such as COUNT, SUM, MAX, MIN or AVG.
`ManaPHP\\Mvc\\Model` allows to use these functions directly from the exposed methods.

Count examples:

```php
    <?php

    // How many cites are?
    $rowCount = City::count();

    // How many different country_id are assigned to cities?
    $rowCount = City::count(['distinct' => 'country_id']);

    // How many cities are in the country_id equal to 10?
    $rowCount = City::count(['country_id' => 10]);

    // Count cites grouping results by their country_id
    $group = City::count(['','group' => 'country_id']);
    foreach ($group as $row) {
       echo 'There are ', $row->row_count, ' in ', $row->area;
    }

    // Count cites grouping by their country_id and ordering the result by count
    $group = City::count([
            '',
            'group' => 'area',
            'order' => 'row_count'
        ]);

    // Avoid SQL injections using bound parameters
    $group = City::count([
            'type > ?0',
            'bind' => [$type]
        ]);
```

Sum examples:

```php
    <?php

    // How much are the salaries of all employees?
    $total = Employee::sum('salary');

    // How much are the salaries of all employees in the Sales area?
    $total = Employee::sum('salary',['area'=> 'Sales']);

    // Generate a grouping of the salaries of each area
    $group = Employee::sum('salary',['','group'  => 'area']);
    foreach ($group as $row) {
       echo 'The sum of salaries of the ', $row->area, ' is ', $row->summary;
    }

    // Generate a grouping of the salaries of each area ordering
    // salaries from higher to lower
    $group = Employee::sum('salary',['','group' => 'area','order' => 'summary DESC']);

    // Avoid SQL injections using bound parameters
    $group = Employee::sum('salary',
            [
            'conditions' => 'area > :area',
            'bind'       => ['area'=>0]
        ]
    );
```

Average examples:

```php
    <?php

    // What is the average salary for all employees?
    $average = Employee::average('salary');

    // What is the average salary for the Sales's area employees?
    $average = Employee::average('salary',['area' => 'Sales']);
```

Max/Min examples:

```php
    <?php

    // What is the oldest age of all employees?
    $age = Employee::maximum('age');

    // What is the oldest of employees from the Sales area?
    $age = Employee::maximum('age',['area' => 'Sales']);

    // What is the lowest salary of all employees?
    $salary = Employee::minimum('salary');
```

## Creating Records

The method `ManaPHP\Mvc\Model::create()` allows you to create records.

```php
    <?php

    $city = new City();
    $city->city_name = 'beijing';
    $city->country_id = 200;

    try{
        $city->create();

        echo 'Great, a new city was saved successfully!';
    }catch(\Exception $e){
        echo "Umh, We can't store city right now: \n";
    }
```

An array could be passed to "create" to avoid assign every column manually:

```php
    <?php

    $city = new City();

    $city->create(
        array(
            'city_name' => 'beijing',
            'country_id' => 200,
        )
    );
```

Values assigned directly or via the array of attributes are escaped/sanitized according to the related attribute data type. So you can pass
an insecure array without worrying about possible SQL injections:

```php
    <?php

    $city = new City();
    $city->create($_POST);
```

>   Without precautions mass assignment could allow attackers to set any database column's value. Only use this feature
    if you want to permit a user to insert every column in the model, even if those fields are not in the submitted
    form.

You can set an additional parameter in 'create' to set a whitelist of fields that only must taken into account when doing
the mass assignment:

```php
    <?php

    $city = new City();

    $city->create(
        $_POST,
        array(
            'city_name',
            'country_id'
        )
    );
```

### Auto-generated identity columns

Some models may have identity columns. These columns usually are the primary key of the mapped table. `ManaPHP\\Mvc\\Model`
can recognize the identity column omitting it in the generated SQL INSERT, so the database system can generate an auto-generated value for it.
Always after creating a record, the identity field will be registered with the value generated in the database system for it:

```php
    <?php

    $city->create();

    echo 'The generated id is: ', $city->city_id;
```

`ManaPHP\\Mvc\\Model` is able to recognize the identity column. Depending on the database system, those columns may be
serial columns like in PostgreSQL or auto_increment columns in the case of MySQL.

PostgreSQL uses sequences to generate auto-numeric values, by default, ManaPHP tries to obtain the generated value from the sequence "table_field_seq",
for example: city_id_seq, if that sequence has a different name, the method "getSequenceName" needs to be implemented:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function getSequenceName()
        {
            return 'city_sequence_name';
        }
    }
```

### Inserting Events

Models allow you to implement events that will be thrown when performing an insert. They help define business rules for a
certain model. The following are the events supported by `ManaPHP\\Mvc\\Model` for insert and their order of execution:

| Operation          | Event                    | Can stop operation?   | Runs before the required operation over the database system                                                                       |
|--------------------|:-------------------------|:----------------------|:----------------------------------------------------------------------------------------------------------------------------------|
| Inserting          | `beforeCreate`           | YES                   | Runs before the required operation over the database system only when an inserting operation is being made                        |
| Inserting          | `afterCreate`            | NO                    | Runs after the required operation over the database system only when an inserting operation is being made                         |
| Inserting/Updating | `afterSave`              | NO                    | Runs after the required operation over the database system                                                                        |

### Implementing Inserting Events in the Model's class

The easier way to make a model react to events is implement a method with the same name of the event in the model's class:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function beforeCreate()
        {
            echo 'This is executed before creating a City!';
        }
    }
```

Events can be useful to assign values before performing an operation, for example:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class Product extends Model
    {
        public function beforeCreate()
        {
            // Set the creation date
            $this->created_at = date('Y-m-d H:i:s');
        }
    }
```

### Implementing a Inserting Business Rule

When an insert, update or delete is executed, the model verifies if there are any methods with the names of
the events listed in the table above.
We recommend that validation methods are declared protected to prevent that business logic implementation
from being exposed publicly.
The following example implements an event that validates the year cannot be smaller than 0 on update or insert:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function beforeCreate()
        {
            if ($this->country_id < 0) {
                echo 'country_id cannot be smaller than zero!';
                return false;
            }
        }
    }
```

Some events return false as an indication to stop the current operation. If an event doesn't return anything, `ManaPHP\\Mvc\\Model` will assume a true value.

## Updating Records

The method `ManaPHP\Mvc\Model::update()` allows you to update records. Also the method executes events that are defined in the model:

```php
    <?php

    $city=City::findFirst(10);
    try{
        $city->update();

        echo 'Great, city was updated successfully!';
    }catch(\Exception $e){
        echo "Umh, We can't store city right now: \n";
    }
```

An array could be passed to "update" to avoid assign every column manually:

```php
    <?php

    $city=City::findFirst(10);

    $city->update(
        array(
            'city_name' => 'beijing',
            'country_id' => 200,
        )
    );
```

Values assigned directly or via the array of attributes are escaped/sanitized according to the related attribute data type. So you can pass
an insecure array without worrying about possible SQL injections:

```php
    <?php

    $city=City::findFirst(10);
    $city->update($_POST);
```

>   Without precautions mass assignment could allow attackers to set any database column's value. Only use this feature
    if you want to permit a user to insert/update every column in the model, even if those fields are not in the submitted
    form.

You can set an additional parameter in 'update' to set a whitelist of fields that only must taken into account when doing
the mass assignment:

```php
    <?php

    $city=City::findFirst(10);

    $city->update(
        $_POST,
        array(
            'city_name',
            'country_id'
        )
    );
```

### Updating Events

Models allow you to implement events that will be thrown when performing an insert/update/delete. They help define business rules for a
certain model. The following are the events supported by `ManaPHP\\Mvc\\Model` and their order of execution:

| Operation          | Event                    | Can stop operation?   | Runs before the required operation over the database system                                                                       |
|--------------------|:-------------------------|:----------------------|:----------------------------------------------------------------------------------------------------------------------------------|
| Updating           | `beforeUpdate`           | YES                   | Runs before the required operation over the database system only when an updating operation is being made                         |
| Updating           | `afterUpdate`            | NO                    | Runs after the required operation over the database system only when an updating operation is being made                          |
| Inserting/Updating | `afterSave`              | NO                    | Runs after the required operation over the database system                                                                        |

### Implementing Updating Events in the Model's class

The easier way to make a model react to events is implement a method with the same name of the event in the model's class:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function beforeUpdate()
        {
            echo 'This is executed before updating a City!';
        }
    }
```

Events can be useful to assign values before performing an operation, for example:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class Product extends Model
    {
        public function beforeUpdate()
        {
            // Set the modification date
            $this->modified_in = date('Y-m-d H:i:s');
        }
    }
```

### Implementing a Updating Business Rule

When an insert, update or delete is executed, the model verifies if there are any methods with the names of
the events listed in the table above.
We recommend that validation methods are declared protected to prevent that business logic implementation
from being exposed publicly.
The following example implements an event that validates the year cannot be smaller than 0 on update or insert:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function beforeSave()
        {
            if ($this->country_id < 0) {
                echo 'country_id cannot be smaller than zero!';
                return false;
            }
        }
    }
```

Some events return false as an indication to stop the current operation. If an event doesn't return anything, `ManaPHP\\Mvc\\Model` will assume a true value.

## Saving Records

The method `ManaPHP\Mvc\Model::save()` allows you to create/update records according to whether they already exist in the table
associated with a model. The save method is called internally by the create and update methods of `ManaPHP\\Mvc\\Model`.
For this to work as expected it is necessary to have properly defined a primary key in the entity to determine whether a record
should be updated or created.

Also the method executes events that are defined in the model:

```php
    <?php

    $city = new City();
    $city->city_name = 'beijing';
    $city->country_id = 200;

    try{
        $city->save();

        echo 'Great, a new city was saved successfully!';
    }catch(\Exception $e){
        echo "Umh, We can't store city right now: \n";
    }
```

An array could be passed to "save" to avoid assign every column manually:

```php
    <?php

    $city = new City();

    $city->save(
        array(
            'city_name' => 'beijing',
            'country_id' => 200,
        )
    );
```

Values assigned directly or via the array of attributes are escaped/sanitized according to the related attribute data type. So you can pass
an insecure array without worrying about possible SQL injections:

```php
    <?php

    $city = new City();
    $city->save($_POST);
```

>   Without precautions mass assignment could allow attackers to set any database column's value. Only use this feature
    if you want to permit a user to insert/update every column in the model, even if those fields are not in the submitted
    form.

You can set an additional parameter in 'save' to set a whitelist of fields that only must taken into account when doing
the mass assignment:

```php
    <?php

    $city = new City();

    $city->save(
        $_POST,
        array(
            'city_name',
            'country_id'
        )
    );
```

### Saving with Confidence

When an application has a lot of competition, we could be expecting create a record but it is actually updated. This
could happen if we use `ManaPHP\Mvc\Model::save()` to persist the records in the database. If we want to be absolutely
sure that a record is created or updated, we can change the `save()` call with `create()` or `update()`:

```php
    <?php

    $city = new City();
    $city->city_name = 'Beijing';
    $city->country_id = 1952;

    // This record only must be created
    try{
        $city->create();

        echo 'Great, a new city was saved successfully!';
    }catch(\Exception $e){
        echo "Umh, We can't store city right now: \n";
    }
```

These methods "create" and "update" also accept an array of values as parameter.

## Deleting Records

The method `ManaPHP\Mvc\Model::delete()` allows to delete a record. You can use it as follows:

```php
    <?php

    $city = City::findFirst(11);

    if ($city != false) {
        if ($city->delete() == false) {
            echo "Sorry, we can't delete the city right now: \n";
        } else {
            echo 'The city was deleted successfully!';
        }
    }
```

You can also delete many records by traversing a resultset with a foreach:

```php
    <?php

    foreach (City::find(['country_id'=>1]) as $city) {
        if ($city->delete() == false) {
            echo "Sorry, we can't delete the city right now: \n";
        } else {
            echo 'The city was deleted successfully!';
        }
    }
```

### Deleting Event
The following events are available to define custom business rules that can be executed when a delete operation is
performed:

| Operation | Name           | Can stop operation? | Explanation                              |
|-----------|:---------------|:--------------------|:-----------------------------------------|
| Deleting  | `beforeDelete` | YES                 | Runs before the delete operation is made |
| Deleting  | `afterDelete`  | NO                  | Runs after the delete operation was made |

With the above events can also define business rules in the models:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function beforeDelete()
        {
            if ($this->status === 'A') {
                echo "The city is active, it can't be deleted";

                return false;
            }

            return true;
        }
    }
```

## Avoiding SQL injections

Every value assigned to a model attribute is escaped depending of its data type. A developer doesn't need to escape manually
each value before storing it on the database. ManaPHP uses internally the [bound parameters](http://php.net/manual/en/pdostatement.bindparam.php)
capability provided by PDO to automatically escape every value to be stored in the database.

```bash
    mysql> DESC product;
    +------------------+------------------+------+-----+---------+----------------+
    | Field            | Type             | Null | Key | Default | Extra          |
    +------------------+------------------+------+-----+---------+----------------+
    | id               | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
    | type_id          | int(10) unsigned | NO   | MUL | NULL    |                |
    | name             | varchar(70)      | NO   |     | NULL    |                |
    | price            | decimal(16,2)    | NO   |     | NULL    |                |
    | active           | char(1)          | YES  |     | NULL    |                |
    +------------------+------------------+------+-----+---------+----------------+
    5 rows in set (0.00 sec)
```

If we use just PDO to store a record in a secure way, we need to write the following code:

```php
    <?php

    $name           = 'Artichoke';
    $price          = 10.5;
    $active         = 'Y';
    $type_id        = 1;

    $sql = 'INSERT INTO product VALUES (null, :type_id, :name, :price, :active)';
    $sth = $dbh->prepare($sql);

    $sth->bindParam(':type_id', $type_id, PDO::PARAM_INT);
    $sth->bindParam(':name', $name, PDO::PARAM_STR, 70);
    $sth->bindParam(':price', doubleval($price));
    $sth->bindParam(':active', $active, PDO::PARAM_STR, 1);

    $sth->execute();
```

The good news is that ManaPHP do this for you automatically:

```php
    <?php

    $product                   = new Product();
    $product->type_id          = 1;
    $product->name             = 'Artichoke';
    $product->price            = 10.5;
    $product->active           = 'Y';

    $product->create();
```

## Record Snapshots

Models could be maintained a record snapshot when they're queried. You can use this feature to implement auditing or just to know what fields are changed according to the data queried from the persistence.
The application consumes a bit more of memory to keep track of the original values obtained from the persistence. you can check what fields changed:

```php
    <?php

    // Get a record from the database
    $city = City::findFirst();

    // Change a column
    $city->city_name = 'Other name';

    var_dump($city->getChangedFields()); // ['city_name']
    var_dump($city->hasChanged('city_name')); // true
    var_dump($city->hasChanged('city_id')); // false
```

## Setting multiple databases

In ManaPHP, all models can belong to the same database connection or have an individual one. Actually, when
`ManaPHP\\Mvc\\Model` needs to connect to the database it requests the "`db`" service
in the application's services container. You can overwrite this service setting it in the initialize method:

```php
    <?php

    use ManaPHP\Db\Adapter\Pdo\Mysql;
    use ManaPHP\Db\Adapter\Pdo\PostgreSQL;

    // This service returns a MySQL database
    $di->set('dbMysql', function () {
        return new Mysql(
            array(
                'host'     => 'localhost',
                'username' => 'root',
                'password' => 'secret',
                'dbname'   => 'invo'
            )
        );
    });

    // This service returns a PostgreSQL database
    $di->set('dbPostgres', function () {
        return new PostgreSQL(
            array(
                'host'     => 'localhost',
                'username' => 'postgres',
                'password' => '',
                'dbname'   => 'invo'
            )
        );
    });
```

Then, in the initialize method, we define the connection service for the model:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function initialize()
        {
            $this->setConnectionService('dbPostgres');
        }
    }
```

But ManaPHP offers you more flexibility, you can define the connection that must be used to 'read' and for 'write'. This is specially useful
to balance the load to your databases implementing a master-slave architecture:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function initialize()
        {
            $this->setReadConnectionService('dbSlave');
            $this->setWriteConnectionService('dbMaster');
        }
    }
```

## Logging Low-Level SQL Statements

When using high-level abstraction components such as `ManaPHP\\Mvc\\Model` to access a database, it is
difficult to understand which statements are finally sent to the database system. `ManaPHP\\Mvc\\Model`
is supported internally by `ManaPHP\\Db`. `ManaPHP\\Log\\Logger` interacts with `ManaPHP\\Db`,
providing logging capabilities on the database abstraction layer, thus allowing us to log SQL statements as they happen.

```php
    <?php

    use ManaPHP\Logger;
    use ManaPHP\Event\Manager;
    use ManaPHP\Db\Adapter\Pdo\Mysql;

    $di->set('db', function () use($logger) {

        $logger = new \ManaPHP\Log\Logger();
        $logger->addAdapter(new \ManaPHP\Log\Adapter\File('app/logs/debug.log'));

        $connection = new Mysql(
            array(
                'host'     => 'localhost',
                'username' => 'root',
                'password' => 'secret',
                'dbname'   => 'invo'
            )
        );
        $connection->attachEvent('db:beforeQuery', function ($event, DbInterface $source, $data) use ($logger) {
                    $logger->debug('SQL: ' . $source->getSQLStatement());
                });

        return $connection;
    });
```

As models access the default database connection, all SQL statements that are sent to the database system will be logged in the file:

```php
    <?php

    $city = new City();
    $city->city_name = 'Robby';
    $city->country_id=100;
    try{
        $city->save();
    }catch(\Exception $e){
        echo 'Cannot save city';
    }
```

As above, the file *app/logs/db.log* will contain something like this:

```bash

    [Mon, 30 Apr 12 13:47:18 -0500][DEBUG] SQL: INSERT INTO city
    (city_name, country_id) VALUES (:city_name, :country_id)
```

## Injecting services into Models

You may be required to access the application services within a model, the following example explains how to do that:

```php
    <?php

    use ManaPHP\Mvc\Model;

    class City extends Model
    {
        public function notSaved()
        {
            $this->logger->debug('not saved');
        }
    }
```

## Stand-Alone component

Using `ManaPHP\\Mvc\\Model` in a stand-alone mode can be demonstrated below:

```php
    <?php

    use ManaPHP\Di;
    use ManaPHP\Mvc\Model;
    use ManaPHP\Db\Adapter\Pdo\Sqlite;
    use ManaPHP\Mvc\Model\Metadata\Memory as MetaData;

    $di = new Di();

    // Setup a connection
    $di->set(
        'db',
        new Sqlite(
            array(
                'dbname' => 'sample.db'
            )
        )
    );

    // Use the memory meta-data adapter or other
    $di->set('modelsMetadata', new MetaData());

    // Create a model
    class City extends Model
    {

    }

    // Use the model
    echo City::count();
```

[PDO]: http://php.net/manual/en/pdo.prepared-statements.php
[date]: http://php.net/manual/en/function.date.php
[time]: http://php.net/manual/en/function.time.php
[Traits]: http://php.net/manual/en/language.oop5.traits.php
