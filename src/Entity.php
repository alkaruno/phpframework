<?php

namespace Xplosio\PhpFramework;

class Entity
{
    /**
     * Gets entity row
     *
     * @static
     * @param $table
     * @param $id
     * @return mixed|null
     */
    public static function getRow($table, $id)
    {
        return Db::getRow('SELECT * FROM `' . $table . '` WHERE id = ?', $id);
    }

    public static function getRows($table, $where = null, $order = null, $from = null, $count = null)
    {
        $arr = array();
        $arr[] = 'SELECT * FROM `' . $table . '`';

        if ($where != null) {
            $arr[] = 'WHERE ' . $where;
        }

        if ($order != null) {
            $arr[] = 'ORDER BY ' . $order;
        }

        if ($count != null) {
            if ($from != null) {
                $arr[] = 'LIMIT ' . $from . ', ' . $count;
            } else {
                $arr[] = 'LIMIT ' . $count;
            }
        }

        return Db::getRows(implode(' ', $arr));
    }

    /**
     * Creates or updates entity row
     *
     * @static
     * @param $table
     * @param $data
     * @param string $id
     * @return string
     */
    public static function save($table, $data, $id = 'id')
    {
        if (is_numeric($id)) {
            $data['id'] = $id;
            $id = 'id';
        }

        if (isset($data[$id]) && Db::getValue('SELECT COUNT(1) FROM `' . $table . '` WHERE `' . $id . '` = ?', $data[$id]) > 0) {
            Db::update('UPDATE `' . $table . '` SET ' . self::getFieldsSql($data, $id) . ' WHERE `' . $id . '` = :' . $id, $data);
            $id = $data[$id];
        } else {
            $id = Db::insert($table, $data);
        }

        return $id;
    }

    public static function delete($table, $id)
    {
        return Db::update('DELETE FROM `' . $table . '` WHERE id = ?', $id);
    }

    public static function getFieldsSql($data, $idColumn = null)
    {
        $sql = array();
        foreach ($data as $field => $value) {
            if ($field != $idColumn) {
                $sql[] = '`' . $field . '` = :' . $field;
            }
        }
        return implode(', ', $sql);
    }
}