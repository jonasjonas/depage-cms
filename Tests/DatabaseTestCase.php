<?php

namespace Depage\XmlDb\Tests;

class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    // {{{ variables
    protected $pdo = null;
    protected $conn = null;
    // }}}

    // {{{ setUp
    protected function setUp() {
        $this->getConnection();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        parent::setUp();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    }
    // }}}
    // {{{ getConnection
    final public function getConnection()
    {
        $this->pdo = new \Depage\Db\Pdo(
            $GLOBALS['DB_DSN'],
            $GLOBALS['DB_USER'],
            $GLOBALS['DB_PASSWD'],
            array(
                'prefix' => 'xmldb',
                \PDO::ATTR_PERSISTENT => true,
            )
        );
        $this->conn = $this->createDefaultDBConnection($this->pdo->getPdoObject(), $GLOBALS['DB_DBNAME']);

        return $this->conn;
    }
    // }}}
    // {{{ getDataSet
    protected function getDataSet() {
        return $this->createXMLDataSet(__DIR__.'/xmldb_dataset.xml');
    }
    // }}}

    // {{{ prepareDatabase
    public static function prepareDatabase()
    {
        $pdo = new \Depage\Db\Pdo(
            $GLOBALS['DB_DSN'],
            $GLOBALS['DB_USER'],
            $GLOBALS['DB_PASSWD']
        );

        $schema = new \Depage\Db\Schema($pdo);
        $schema->setReplace(
            function ($name)
            {
                return 'xmldb_proj_test' . $name;
            }
        );
        $schema->loadGlob(__DIR__ . '/../Sql/*.sql');
        $schema->loadGlob(__DIR__ . '/Sql/*.sql');

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        $schema->update();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    }
    // }}}

    // {{{ tableExists
    protected function tableExists($tableName)
    {
        $exists = false;

        try {
            $this->pdo->query('SELECT 1 FROM ' . $tableName);
            $exists = true;
        } catch (\PDOException $expected) {
            // only catch "table doesn't exist" exception
            if ($expected->getCode() != '42S02') {
                throw $expected;
            }
        }

        return $exists;
    }
    // }}}
    // {{{ dropTable
    protected function dropTable($tableName)
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        $this->pdo->query('DROP TABLE IF EXISTS ' . $tableName);
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
        $this->assertFalse($this->tableExists($tableName));
    }
    // }}}
    // {{{ dropTables
    protected function dropTables($tableNames)
    {
        foreach($tableNames as $tableName) {
            $this->dropTable($tableName);
        }
    }
    // }}}
    // {{{ insertDummyDataIntoTable
    protected function insertDummyDataIntoTable($tableName)
    {
        $statement = $this->pdo->query('DESCRIBE ' . $tableName . ';');
        $statement->execute();
        while ($row = $statement->fetch()) {
            $values[] = '" "';
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        $rows = $this->pdo->exec('INSERT INTO ' . $tableName . ' VALUES (' . implode(',', $values) . ');');
        $this->assertEquals(1, $rows);
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    }
    // }}}

    // {{{ assertTableEmpty
    protected function assertTableEmpty($tableName)
    {
        $statement = $this->pdo->query('SELECT COUNT(*) FROM ' . $tableName . ';');
        $statement->execute();
        $result = $statement->fetch();

        $this->assertEquals(0, $result['COUNT(*)']);
    }
    // }}}

    // {{{ assertXmlStringEqualsXmlStringIgnoreAttributes
    protected function assertXmlStringEqualsXmlStringIgnoreAttributes($expected, $actual, $attributes = array(), $message = '')
    {
        foreach ($attributes as $attribute) {
            $regex = preg_quote($attribute .'=') . '"[^"]*"';
            $actual = preg_replace('#' . $regex . '#', '', $actual);
            $expected = preg_replace('#' . $regex . '#', '', $expected);
        }

        return $this->assertXmlStringEqualsXmlString($expected, $actual, $message);
    }
    // }}}
    // {{{ assertXmlStringEqualsXmlStringIgnoreLastchange
    protected function assertXmlStringEqualsXmlStringIgnoreLastchange($expected, $actual, $message = '')
    {
        return $this->assertXmlStringEqualsXmlStringIgnoreAttributes(
            $expected,
            $actual,
            array(
                'db:lastchange',
                'db:lastchangeUid',
            ),
            $message
        );
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
