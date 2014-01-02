Yii2 Extend
===============

A various collection of classes.

* [Behaviors/SoftDelete] (#softdelete)
* [Test/DbToDbFixtureManager] (#dbtodbfixturemanager)

### Installation
* Install via composer ```"amnah/yii2-classes": "dev-master"```

### SoftDelete
This class adds soft-delete functionality to ActiveRecord models.

Before you use this, please see [this response](http://stackoverflow.com/a/2549940) to decide
if you really need this functionality.

#### Usage
* Add column ``` `delete_time` ``` (int or timestamp) to your database table
* Add behavior to your model

```php
public function behaviors() {
    return [
        'softDelete' => [
            'class' => 'amnah\yii2\behaviors\SoftDelete',
            // these are the default values, which you can omit
            'attribute' => 'delete_time',
            'timestamp' => time(), // this is the same format as in AutoTimestamp
            'safeMode' => true, // this processes '$model->delete()' calls as soft-deletes
        ],
    ];
}
```

* Call functions

```php
// soft-delete model
$model->remove();

// restore model
$model->restore();

// delete model from db
$model->forceDelete();

// soft-delete model if $safeMode = true
// delete model from db if $safeMode = false
$model->delete();
```

* *Optional* - Add a default scope to your model to exclude soft-deleted records

```php
class Model extends ActiveRecord {
    public static function createQuery() {
        $condition = ["delete_time" => null];
        $query = new \yii\db\ActiveQuery(['modelClass' => get_called_class()]);
        return $query->andWhere($condition);
    }
}
```

### DbToDbFixtureManager

The DbToDbFixtureManager class loads fixtures in from another database instead of from php arrays. 

This is useful if you constantly change your db schema. For example, you can simply copy your 
developmentDb via phpMyAdmin and use that as your fixtureDb - no need to go through several 
php files and manually update data. From there, it's also easier to manipulate the data when 
you have sql commands at your disposal.

Additionally, this is useful if you have lots of fixture data. It is significantly faster to 
copy tables in sql than it is to load arrays in php and manually insert each individual record 
(which is the current implementation of DbFixtureManager).

**Note:** Currently, the fixtureDb must be on the same db connection as the testDb (same server, user,
and password). This is because loads the data by using 
```insert into `fixtureDb`.`table` select * from `testDb`.`table`.

#### Usage

```php
// @app/test/unit/_config.php
'components' => [
    'fixture' => [
        'class' => 'amnah\yii2\test\DbToDbFixtureManager',
        'fixtureDb' => 'databasename_test',
    ],
]
```