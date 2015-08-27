<?php

use Xplosio\PhpFramework\Db;

class DbTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require '../vendor/autoload.php';

        Db::__init([
            'dsn' => 'mysql:dbname=test;host=localhost;charset=utf8',
            'username' => 'root',
            'password' => 'password',
        ]);

        Db::query('DROP TABLE IF EXISTS `user`');
        Db::query('
            CREATE TABLE `user` (
              `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `username` varchar(32) NOT NULL,
              `password` varchar(255) NOT NULL,
              `create_date` datetime NOT NULL
            ) ENGINE=\'InnoDB\';
        ');
        Db::insert('user', ['username' => 'johndoe', 'password' => md5('password'), 'create_date' => date('r')]);
    }

    public function testGetRow()
    {
        self::assertNull(Db::getRow('SELECT * FROM user WHERE id = ?', 42));
        self::assertTrue(is_array(Db::getRow('SELECT * FROM user WHERE username = ?', 'johndoe')));
    }

    public function testTransaction()
    {
        try {
            Db::begin();
            $this->insertTestUser(true);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
        }

        self::assertNull(Db::getRow('SELECT * FROM user WHERE username = ?', 'test'));

        try {
            Db::transaction(function () {
                $this->insertTestUser(true);
            });
        } catch (\Exception $ignore) {
        }

        self::assertNull(Db::getRow('SELECT * FROM user WHERE username = ?', 'test'));

        try {
            Db::transaction(function () {
                $this->insertTestUser(false);
            });
        } catch (\Exception $ignore) {
        }

        self::assertNotNull(Db::getRow('SELECT * FROM user WHERE username = ?', 'test'));
    }

    private function insertTestUser($withError)
    {
        Db::insert('user', ['username' => 'test', 'password' => md5('password'), 'create_date' => date('r')]);
        if ($withError) {
            Db::insert('INSERT INTO user SET username = ?, password = ?', 'foo');
        } else {
            Db::insert('INSERT INTO user SET username = ?, password = ?', 'foo', 'password');
        }
    }
}
