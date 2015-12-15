<?php

namespace Xplosio\PhpFramework;

use PDO;

class Db
{
    /**
     * @var PDO
     */
    private static $pdo;

    private static $time = 0;
    private static $logs = [];

    private static $transactionCounter = 0;

    const LOG_LIMIT = 100;

    public static function __init($config)
    {
        self::$pdo = new PDO($config['dsn'], $config['username'], $config['password']);
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

        if ($value === false) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $value;
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
        $pairs = [];
        foreach (self::getRows($sql, $values) as $row) {
            $pairs[$row[$keyColumn]] = $row[$valueColumn];
        }

        return $pairs;
    }

    public static function getIterator($sql, $values = null)
    {
        $args = func_get_args();
        $statement = self::internalQuery($sql, self::getValues($values, $args));
        return $statement ?: null;
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
            $pairs = [];
            foreach ($values as $key => $value) {
                $pairs[] = "`{$key}` = ?";
            }
            self::internalQuery('INSERT `' . $sql . '` SET ' . implode(', ', $pairs), array_values($values));
        }

        return self::$pdo->lastInsertId();
    }

    public static function getLastInsertId()
    {
        return self::$pdo->lastInsertId();
    }

    public static function getFoundRows()
    {
        return self::getValue('SELECT FOUND_ROWS()');
    }

    public static function transaction(\Closure $callback)
    {
        self::begin();
        try {
            $result = $callback();
            self::commit();
        } catch (\Exception $e) {
            self::rollback();
            throw $e;
        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }

        return $result;
    }

    public static function begin()
    {
        if (self::$transactionCounter++ === 0) {
            self::$pdo->beginTransaction();
        }
    }

    public static function commit()
    {
        if (--self::$transactionCounter === 0) {
            self::$pdo->commit();
        }
    }

    public static function rollback()
    {
        if (self::$transactionCounter >= 0) {
            self::$pdo->rollBack();
        }
        self::$transactionCounter = 0;
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
            $values = [$values];
        }

        $time = microtime(true);

        $statement = self::$pdo->prepare($sql);

        foreach ($values as $param => $value) {

            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }

            $param = is_int($param) ? (int)$param + 1 : $param;

            $statement->bindValue($param, $value, $type);
        }

        if (!$statement->execute()) {
            $info = $statement->errorInfo();
            if (isset($info[2])) {
                throw new \LogicException($sql . PHP_EOL . $info[2], 500);
            }
            $statement = false;
        }

        $time = microtime(true) - $time;
        self::$time += $time;

        if (count(self::$logs) < self::LOG_LIMIT) {
            self::$logs[] = [$sql, $values, $time];
        }

        if (function_exists('sql_query_log')) {
            sql_query_log($sql, $values, $time);
        }

        return $statement;
    }

    private static function getValues($values, $args)
    {
        if (!is_array($values) || count($args) > 2) {
            $values = $args;
            array_shift($values);
        }

        return $values;
    }

    public static function getPdo()
    {
        return self::$pdo;
    }
}
