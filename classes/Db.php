<?php

namespace Xplosio\PhpFramework;

use Exception;
use PDO;
use PDOStatement;

class Db
{
    /**
     * @var PDO
     */
    private static $pdo;

    private static $time = 0;
    private static $logs = array();

    const LOG_LIMIT = 100; // количество запросов, которые помещаются в лог (чтобы не было переполнения памяти, если запросов очень и очень много)

    public static function __init()
    {
        $config = App::$config['db'];
        self::$pdo = new PDO($config['dsn'], $config['username'], $config['password']);
        self::$pdo->exec('SET NAMES ' . (isset($config['charset']) ? $config['charset'] : 'utf8'));
    }

    public static function query($sql, $values = null)
    {
        $args = func_get_args();
        self::internalQuery($sql, self::getValues($values, $args));
    }

    public static function getValue($sql, $values = null)
    {
        $args = func_get_args();
        $statement = self::internalQuery($sql, self::getValues($values, $args));
        $value = $statement->fetchColumn();

        return $value !== false ? $value : null;
    }

    public static function getRow($sql, $values = null)
    {
        $args = func_get_args();
        $statement = self::internalQuery($sql, self::getValues($values, $args));
        if ($statement) {
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        }
        return null;
    }

    public static function getRows($sql, $values = null)
    {
        $args = func_get_args();
        $statement = self::internalQuery($sql, self::getValues($values, $args));
        return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : null;
    }

    public static function getRowsById($sql, $values = null, $idColumn = 'id', $multiValues = false)
    {
        $result = array();
        foreach (self::getRows($sql, $values) as $row) {
            $id = $row[$idColumn];
            if ($multiValues) {
                if (!isset($result[$id])) {
                    $result[$id] = array();
                }
                $result[$id][] = $row;
            } else {
                $result[$id] = $row;
            }
        }
        return $result;
    }

    public static function getPairs($sql, $values = null, $keyColumn = 'id', $valueColumn = 'name')
    {
        $pairs = array();
        foreach (self::getRows($sql, $values) as $row) {
            $pairs[$row[$keyColumn]] = $row[$valueColumn];
        }
        return $pairs;
    }

    public static function update($sql, $values = null)
    {
        $args = func_get_args();
        $statement = self::internalQuery($sql, self::getValues($values, $args));
        return $statement ? $statement->rowCount() : null;
    }

    public static function insert($sql, $values = null)
    {
        if (strpos($sql, ' ') !== false) {
            $args = func_get_args();
            self::internalQuery($sql, self::getValues($values, $args));
        } else {
            $sql = sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s)',
                $sql,
                implode(',', array_keys($values)),
                implode(',', array_fill(0, count($values), '?'))
            );
            self::internalQuery($sql, array_values($values));
        }

        return self::$pdo->lastInsertId();
    }

    public static function getFoundRows()
    {
        return self::getValue('SELECT FOUND_ROWS()');
    }

    public static function begin()
    {
        self::$pdo->beginTransaction();
    }

    public static function commit()
    {
        self::$pdo->commit();
    }

    public static function rollback()
    {
        self::$pdo->rollBack();
    }

    public static function getTime()
    {
        return self::$time;
    }

    public static function getLogs()
    {
        return self::$logs;
    }

    private static function internalQuery($sql, $values = null)
    {
        if (!is_array($values)) {
            $values = array($values);
        }

        $time = microtime(true);

        /**
         * @var PDOStatement
         */
        $statement = self::$pdo->prepare($sql);

        foreach ($values as $param => $value) {

            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } else if (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } else if (is_null($value)) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }

            $param = is_int($param) ? intval($param) + 1 : $param;

            $statement->bindValue($param, $value, $type);
        }

        if (!$statement->execute()) {
            $info = $statement->errorInfo();
            if (isset($info[2])) {
                throw new Exception($sql . PHP_EOL . $info[2], 500);
            }
            $statement = false;
        }

        $time = microtime(true) - $time;
        self::$time += $time;

        if (count(self::$logs) < self::LOG_LIMIT) {
            self::$logs[] = array($sql, $values, $time);
        }

        if (function_exists('sql_query_log')) {
            sql_query_log($sql, $values, $time);
        }

        return $statement;
    }

    private static function getValues($values, $args)
    {
        if (count($args) > 2 || !is_array($values)) {
            $values = $args;
            array_shift($values);
        }

        return $values;
    }
}

Db::__init();