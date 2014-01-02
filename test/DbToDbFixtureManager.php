<?php

namespace amnah\yii2\test;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\Exception;
use Codeception\Util\Debug;

/**
 * DbFixtureManager
 *
 * Process fixtures stored in another database. The fixture database must be accessible using the
 * same dbuser and dbpassword as the test database
 *
 * Usage:
 * 
 * ~~~
 * // @app/tests/unit/_config.php
 * 'components' => [
 *     'fixture' => [
 *         'class' => 'amnah\yii2\test\DbToDbFixtureManager',
 *         'fixtureDb' => 'databasename_test',
 *         'testDbReset' => true,
 *     ],
 * ]
 * ~~~
 * @author amnah <amnah.dev@gmail.com>
 */
class DbToDbFixtureManager extends \yii\test\DbFixtureManager {

    /**
     * @var string Name of fixture database
     */
    public $fixtureDb;

    /**
     * @var array Tables to skip for $resetEntireDb
     */
    public $skipTables = ["tbl_migration"];

    /**
     * @var array Fixture data [ row alias => row ]
     */
    protected $_rows;

    /**
     * @var array Fixture models [ row alias => record (or class name) ]
     */
    protected $_models;

    /**
     * @inheritdoc
     */
    public function init() {

        // calculate $this->basePath for init script
        parent::init();
        $this->basePath = Yii::getAlias($this->basePath);

        // calculate $this->db
        if (is_string($this->db)) {
            $this->db = Yii::$app->getComponent($this->db);
        }
        if (!$this->db instanceof Connection) {
            throw new InvalidConfigException("The 'db' property must be either a DB connection instance or the application component ID of a DB connection.");
        }

        // check that fixtureDb was set
        if (!$this->fixtureDb) {
            throw new InvalidConfigException("The 'fixtureDb' property must be set (string)");
        }
    }

    /**
     * Load all fixtures by iterating through all tables in test db
     */
    public function loadAll() {

        // debug
        Debug::debug("\r\n-------------------------------------");
        Debug::debug(get_called_class() . "::" . __FUNCTION__ . "()");

        // loops through all tables in test database
        $fixtures = [];
        $skip = [];
        foreach ($this->db->getSchema()->tableNames as $tableName) {

            // check if we need to skip this table
            if (in_array($tableName, $this->skipTables)) {
                $skip[] = $tableName;
                continue;
            }

            // add table
            $fixtures[] = $tableName;
        }

        // load fixtures
        Debug::debug("  Attempting to load: " . implode(" / ", $fixtures));
        Debug::debug("  Skipping (\$skipTables): " . implode(" / ", $skip));
        $this->load($fixtures);

    }

    /**
     * @inheritdoc
     */
    public function load(array $fixtures = []) {

        // convert fully qualified name $fixture into object to get tableName
        /** @var \yii\db\ActiveRecord $model */
		foreach ($fixtures as $name => $fixture) {
			if (strpos($fixture, '\\') !== false) {
				$model = new $fixture;
				if ($model instanceof ActiveRecord) {
					$fixtures[$name] = $model->getTableSchema()->name;
				} else {
					throw new InvalidConfigException("Fixture '$fixture' must be an ActiveRecord class.");
				}
			}
		}

        // reset data
        $this->_rows = $this->_models = [];

        // remove fk integrity checks
        $this->checkIntegrity(false);
        Debug::debug("\r\n-------------------------------------");
        Debug::debug(get_called_class() . "::" . __FUNCTION__ . "()");

        // run init script if it exists
		if (!empty($this->initScript)) {
			$initFile = $this->basePath . '/' . $this->initScript;
			if (is_file($initFile)) {
                Debug::debug("Running {$this->initScript}");
				require($initFile);
			}
		}

        // load fixtures
		foreach ($fixtures as $name => $tableName) {
			$this->_rows[$name] = $this->loadFixture($tableName);
		}

        // put fk integrity check back in
        $this->checkIntegrity(true);
        Debug::debug("Done loading fixtures");
        Debug::debug("-------------------------------------");
	}

	/**
	 * @inheritdoc
     */
    public function loadFixture($tableName) {

        // check for valid table
        $table = $this->db->getSchema()->getTableSchema($tableName);
        if ($table === null) {
            throw new InvalidConfigException("Table does not exist: $tableName");
        }

        // check if fixtureDb contains the table
        try {
            // check existence through simple select statement
            $q = "SELECT 1 FROM `{$this->fixtureDb}`.`$tableName` LIMIT 1";
            $this->db->createCommand($q)->execute();
        }
            // display skip message
        catch (Exception $e) {
            Debug::debug("  Skipping - $tableName (does not exist in \$fixtureDb)");
            return false;
        }

        // truncate table
        $this->db->createCommand()->truncateTable($tableName)->execute();
        $this->db->createCommand()->resetSequence($tableName, 1)->execute();

        // copy table data - will error out if it fails
        $q = "INSERT INTO `$tableName` SELECT * FROM `{$this->fixtureDb}`.`$tableName`";
        $this->db->createCommand($q)->execute();

        // select data to get rows
        $q = "SELECT * FROM `{$this->fixtureDb}`.`$tableName`";
        $rows = $this->db->createCommand($q)->queryAll();

        // debug and return
        Debug::debug("  Loaded fixture - $tableName");
        return $rows;
	}

    /**
     * God I hate private variables
     * @inheritdoc
     */
    public function getRows($fixtureName) {
        return isset($this->_rows[$fixtureName]) ? $this->_rows[$fixtureName] : false;
	}
}
