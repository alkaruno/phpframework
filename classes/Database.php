<?php

class Database
{
    private static $instance;

    private $connection;

    private $logs = array();
    private $time = 0;

    const LOG_LIMIT = 100;

    private function __construct()
    {
        $config = Dispatcher::$config['env']['db'];

        $this->connection = new PDO($config['dsn'], $config['username'], $config['password']);
        $this->connection->exec('SET NAMES ' . (isset($config['encoding']) ? $config['encoding'] : 'utf8'));

        return $this;
    }

    /**
     * @return Database
     */
    public static function & instance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function query($sql, $values = null)
    {
        return $this->internalQuery($sql, $values);
    }

    public function getValue($sql, $values = null)
    {
        $statement = $this->internalQuery($sql, $values);
        return $statement ? $statement->fetchColumn() : null;
    }

    public function getRow($sql, $values = null)
    {
        $statement = $this->internalQuery($sql, $values);
        if ($statement) {
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        }
        return null;
    }

    public function getRows($sql, $values = null)
    {
        $statement = $this->internalQuery($sql, $values);
        return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : null;
    }

    public function getPairs($sql, $values = null, $keyColumn = 'id', $valueColumn = 'name')
    {
        $pairs = array();

        foreach ($this->getRows($sql, $values) as $row) {
            $pairs[$row[$keyColumn]] = $row[$valueColumn];
        }

        return $pairs;
    }

    public function getObject($objectClass, $sql, $values = null)
    {
        // Database::instance()->getObject(User, 'SELECT * FROM user WHERE id = ?', $id);
    }

    public function getRowsById($sql, $values = null, $idColumn = 'id', $multiValues = false)
    {
        $result = array();
        foreach ($this->getRows($sql, $values) as $row) {
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

    public function insert($sql, $values = null)
    {
        $this->internalQuery($sql, $values);
        return $this->connection->lastInsertId();
    }

    public function update($sql, $values = null)
    {
        $statement = $this->internalQuery($sql, $values);
        return $statement ? $statement->rowCount() : null;
    }

    public function begin()
    {
        $this->connection->beginTransaction();
    }

    public function commit()
    {
        $this->connection->commit();
    }

    public function rollback()
    {
        $this->connection->rollBack();
    }

    public function getIterator($query, $values = null)
    {
        $st = $this->internalQuery($query, $values);
        return $st ? $st : null;
    }

    private function internalQuery($sql, $values = null)
    {
        if (!is_array($values)) {
            $values = array($values);
        }

        $time = microtime(true);

        $statement = $this->connection->prepare($sql);

        foreach ($values as $param => $value) {
            $statement->bindValue(is_int($param) ? intval($param) + 1 : $param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        if (!$statement->execute()) {
            $info = $statement->errorInfo();
            if (isset($info[2])) {
                throw new Exception($sql . PHP_EOL . $info[2], 500);
            }
            $statement = false;
        }

        $time = microtime(true) - $time;
        $this->time += $time;

        if (count($this->logs) < self::LOG_LIMIT) {
            $this->logs[] = array($sql, $values, $time);
        }

        return $statement;
    }

    /**
     * @deprecated use Entity::save
     *
     * @param $table
     * @param $data
     * @param string $idColumn
     * @return string
     */
    public function save($table, $data, $idColumn = 'id')
    {
        if (isset($data[$idColumn]) && $this->getValue('SELECT COUNT(1) FROM `' . $table . '` WHERE `' . $idColumn . '` = ?', $data[$idColumn]) > 0) {

            $sql = array();
            foreach ($data as $field => $value) {
                if ($field != $idColumn) {
                    $sql[] = '`' . $field . '` = :' . $field;
                }
            }
            $sql = implode(', ', $sql);

            $this->update('UPDATE `' . $table . '` SET ' . $sql . ' WHERE `' . $idColumn . '` = :' . $idColumn, $data);
            $id = $data[$idColumn];

        } else {

            $sql = array();
            foreach ($data as $field => $value) {
                $sql[] = '`' . $field . '` = :' . $field;
            }
            $sql = implode(', ', $sql);

            $id = $this->insert('INSERT `' . $table . '` SET ' . $sql, $data);
        }

        return $id;
    }

    /**
     * @deprecated
     * TODO move to DataHelper
     *
     * @static
     * @param $data
     * @param string $id
     * @param string $separator
     * @return string
     */
    public static function getIdsString($data, $id = 'id', $separator = ', ')
    {
        $ids = array();
        foreach ($data as $row) {
            $ids[] = $row[$id];
        }
        return implode($separator, $ids);
    }

    public function getLogs()
    {
        return $this->logs;
    }
}