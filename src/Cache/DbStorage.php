<?php

namespace Xplosio\PhpFramework\Cache;

use Xplosio\PhpFramework\Db;

class DbStorage implements Storage
{
    const DEFAULT_TABLE_NAME = 'cache';

    private $table;

    public function __construct($config)
    {
        $this->table = array_key_exists('table', $config) ? $config['table'] : self::DEFAULT_TABLE_NAME;
    }

    public function get($key)
    {
        $row = Db::getRow("SELECT * FROM `$this->table` WHERE id = ?", $key);

        return $row !== null ? [unserialize($row['value']), $row['expiration']] : null;
    }

    public function put($key, $value, $expiration)
    {
        Db::query("DELETE FROM `$this->table` WHERE expiration < ?", time());
        Db::query(
            "REPLACE INTO `$this->table` SET id = ?, value = ?, expiration = ?",
            [$key, serialize($value), $expiration]
        );
    }

    public function remove($key)
    {
        Db::update("DELETE FROM `$this->table` WHERE id = ?", $key);
    }

    public function flush()
    {
        Db::update("DELETE FROM `$this->table`");
    }
}
