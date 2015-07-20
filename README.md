Yii 2 Classes
===============

A various collection of classes. Install using composer
```"amnah/yii2-classes": "dev-master"```.

## Table of Contents

* [Behaviors/SoftDelete] (#softdelete)
* [Widgets/ExtListView] (#extlistview)
* [Test/DbToDbFixtureManager] (#dbtodbfixturemanager)

### SoftDelete
This class adds soft-delete functionality to ActiveRecord models.

Before you use this, please see [this response](http://stackoverflow.com/a/2549940) to decide
if you really need this functionality.

#### Usage
* Add column ``` `delete_time` ``` (int or timestamp **DEFAULT NULL**) to your database table
* Add behavior to your model

```php
public function behaviors() {
    return [
        'softDelete' => [
            'class' => 'amnah\yii2\behaviors\SoftDelete',
            // these are the default values, which you can omit
            'attribute' => 'delete_time',
            'value' => null, // this is the same format as in TimestampBehavior
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

* (Optional) Update model by adding default condition/scope to select only non-deleted records

```php
class Customer extends ActiveRecord
{
    public static function find()
    {
        return parent::find()->where(['delete_time' => null]);
    }
}
```

### ExtListView

The ExtListView class extends the default ```yii\widgets\ListView``` class by adding in
views and closures.

#### Usage

Using views:

```php
// @app/views/list/index.php
<?php echo ExtListView::widget([
    // ...
    "layoutView" => "_list",
    "layoutViewParams" => [
        // variables to pass into layoutView
    ],
    "emptyView" => "_listEmpty",
    "emptyViewParams" => [
        // variables to pass into emptyView
    ],
    // ...
]); ?>
```

```php
// @app/views/list/_list.php
<div>{summary}</div>
<div>{pager}</div>

<div id="listitems" class="row">
    {items}
</div>
```

Using closures:

```php
<?php echo ExtListView::widget([
    // ...
    "layoutView" => function() {
        return '
            <div>{summary}</div>
            <div>{pager}</div>
            <div id="listitems" class="row">
                {items}
            </div>
        ';
    },
    'emptyView' => function() {
        return '<div>nothing found</div>';
    },
    // ...
]); ?>
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

**Note:** Currently, the fixtureDb must be on the same db connection as the testDb (same server,
user, and password). This is because loads the data by using
```insert into `fixtureDb`.`table select * from testDb.table```.

#### Usage

```php
// @app/tests/unit/_config.php
'components' => [
    'fixture' => [
        'class' => 'amnah\yii2\test\DbToDbFixtureManager',
        'fixtureDb' => 'databasename_test',
    ],
]
```

```php
// @app/tests/unit/models/UserTest.php
protected function setUp() {
    parent::setUp();

    // load fixtures using same exact call
    $this->loadFixtures(['tbl_user']);
}
```