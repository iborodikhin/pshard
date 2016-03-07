<?php
namespace PShard;

/**
 * Base test class.
 */
abstract class Test extends \PHPUnit_Framework_TestCase
{
    /**
     * PShard instance.
     *
     * @var \PShard\PShard
     */
    protected static $pshard;

    /**
     * List of files to be removed after test run.
     *
     * @var array
     */
    protected static $filesToRemove = [];

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $mapper = new \PDO('sqlite:' . DATA_DIR . '/mapper.db');
        $mapper->exec("CREATE TABLE real_shard (
            'id' INTEGER PRIMARY KEY ASC,
            'name' VARCHAR(50) UNIQUE NOT NULL,
            'dsn' VARCHAR(50) NOT NULL,
            'username' VARCHAR(50) NOT NULL DEFAULT '',
            'password' VARCHAR(200) NOT NULL DEFAULT '',
            'options' VARCHAR(1000) NOT NULL DEFAULT ''
        );");
        $mapper->exec("CREATE TABLE virtual_shard (
            'id' INTEGER PRIMARY KEY ASC ,
            'virtual' VARCHAR(50) NOT NULL,
            'real' VARCHAR(50) NOT NULL
        );");
        $mapper->exec("CREATE UNIQUE INDEX virtual_real ON virtual_shard('virtual', 'real');");

        self::$filesToRemove[] = DATA_DIR . '/mapper.db';

        for ($i = 0; $i < rand(10, 20); $i++) {
            $shard = new \PDO('sqlite:' . DATA_DIR . '/shard' . $i . '.db');
            $shard->exec("CREATE TABLE user (
                'id' INTEGER PRIMARY KEY ASC,
                'login' VARCHAR(50) UNIQUE  NOT NULL
            )");
            $mapper->exec("INSERT INTO real_shard (name, dsn, username, password, options) VALUES (
                'real_shard" . $i . "',
                'sqlite:" . DATA_DIR . "/shard" . $i . ".db',
                '',
                '',
                ''
            )");
            $mapper->exec("INSERT INTO virtual_shard ('virtual', 'real') VALUES (
                'virtual_shard" . $i . "',
                'real_shard" . $i . "'
            );");
            self::$filesToRemove[] = DATA_DIR . '/shard' . $i . '.db';
        }

        self::$pshard = new \PShard\PShard($mapper);
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        foreach (self::$filesToRemove as $file) {
            unlink($file);
        }
    }
}