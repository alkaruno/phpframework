<?php

class Scaffold
{
    const IMAGE_FIELD_TYPE = 'image';
    const DEFAULT_LIST_SIZE = 25;

    private $fieldsByType = array();
    private $views;

    /**
     * @param $data имя файла с json данными или массив
     * @return array
     */
    public function process($data)
    {
        if (is_string($data)) {
            $data = json_decode(file_get_contents($data), true);
        }

        $db = Database::instance();

        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $action = isset($_GET['action']) ? $_GET['action'] : null;
        $table = $data['table'];

        $errors = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            if ($id != null && isset($_POST['_delete'])) {
                $db->update('DELETE FROM `' . $table . '` WHERE id = ?', $id);
                return 'redirect:' . $data['url'];
            }

            $_POST['id'] = $id;

            /* validation */

            $validator = new Validator();
            foreach ($data['fields'] as $field => $fieldData) {
                if (isset($fieldData['validation'])) {
                    $rule = $validator->add($field);
                    foreach ($fieldData['validation'] as $row) {
                        $rule->addRule($row['type'], isset($row['error']) ? $row['error'] : null, isset($row['value']) ? $row['value'] : null);
                    }
                }
            }

            if ($validator->validate()) {

                /* process fields before save */
                foreach ($data['fields'] as $fieldName => $fieldData) {
                    if (!isset($fieldData['type']) || $fieldData['type'] != self::IMAGE_FIELD_TYPE) {
                        $field = $this->getField($fieldData);
                        $_POST[$fieldName] = $field->getValue($fieldName, $field->getValue($fieldName, $_POST[$fieldName], $fieldData), $fieldData);
                    }
                }

                $id = Entity::save($table, array_map('trim', $_POST));

                /* process images */
                foreach ($data['fields'] as $fieldName => $fieldData) {
                    if (isset($fieldData['type']) && $fieldData['type'] == self::IMAGE_FIELD_TYPE) {
                        $fieldData['id'] = $id;
                        $field = $this->getField($fieldData);
                        $field->getValue($fieldName, null, $fieldData);
                    }
                }

                header('Location: ' . $data['url']);

            } else {

                $errors = $validator->getErrors();

            }

        } else {

            if ($id == null && $action == null) {

                $sql = 'SELECT * FROM `' . $table . '`';
                if (isset($data['list']['order'])) {
                    $sql .= ' ORDER BY ' . $data['list']['order'];
                }
                $size = isset($data['list']['size']) ? $data['list']['size'] : self::DEFAULT_LIST_SIZE;
                $page = new SqlPage($sql, array(), isset($_GET['page']) ? $_GET['page'] : 1, $size);

                $pageContent = $page->getContent();
                foreach ($pageContent as &$row) {
                    foreach ($row as $key => $value) {
                        if (isset($data['fields'][$key])) {
                            $fieldData = $data['fields'][$key];
                            $row[$key] = $this->getField($fieldData)->getViewHtml($key, $value, $fieldData);
                        }
                    }
                }
                $page->setContent($pageContent);

                return array($this->views['list'], array('data' => $data, 'page' => $page));
            }

            if ($id != null) {
                $_POST = $db->getRow('SELECT * FROM `' . $table . '` WHERE id = ?', $id);
            }

        }

        $editors = array();
        foreach ($data['fields'] as $fieldName => $fieldData) {
            $fieldData['id'] = $id;
            $field = $this->getField($fieldData);
            $editors[$fieldName] = $field->getEditHtml($fieldName, $field->getValue($fieldName, isset($_POST[$fieldName]) ? $_POST[$fieldName] : '', $fieldData), $fieldData);
        }

        return array($this->views['form'], array('data' => $data, '_editors' => $editors, 'errors' => $errors));
    }

    /**
     * @param $fieldData
     * @return mixed
     */
    private function getField($fieldData)
    {
        $fieldType = isset($fieldData['type']) ? $fieldData['type'] : 'text';

        if (!isset($this->fieldsByType[$fieldType])) {
            $field = ucfirst($fieldType) . 'Field';
            $this->fieldsByType[$fieldType] = new $field;
        }

        return $this->fieldsByType[$fieldType];
    }

    public function setViews($views)
    {
        $this->views = $views;
    }
}

interface Field
{
    /**
     * Отображение значения из БД
     *
     * @abstract
     * @param $field
     * @param $value
     * @param array $data
     * @return string
     */
    public function getViewHtml($field, $value, $data);

    /**
     * Вывод редактора для поля
     *
     * @abstract
     * @param $field
     * @param $value
     * @param array $data
     * @return mixed
     */
    public function getEditHtml($field, $value, $data);

    /**
     * Конвертирование из значения поля в значение БД
     *
     * @abstract
     * @param $field
     * @param $value
     * @param array $data
     * @return mixed
     */
    public function getValue($field, $value, $data);
}

class TextField implements Field
{
    public function getViewHtml($field, $value, $data)
    {
        return $value;
    }

    public function getEditHtml($field, $value, $data)
    {
        return '<input type="text" name="' . $field . '" id="' . $field . '" value="' . htmlspecialchars($value) . '" class="span12">';
    }

    public function getValue($field, $value, $data)
    {
        return $value;
    }
}

class TextareaField extends TextField
{
    public function getEditHtml($field, $value, $data)
    {
        return '<textarea name="' . $field . '" id="' . $field . '" class="span12'. (isset($data['wysiwyg']) && $data['wysiwyg'] ? ' editor' : '') .'" rows="15">' . htmlspecialchars($value) . '</textarea>';
    }
}

class DateField implements Field
{
    public function getViewHtml($field, $value, $data)
    {
        return !empty($value) ? date('d.m.Y', strtotime($value)) : '';
    }

    public function getEditHtml($field, $value, $data)
    {
        return '<input type="text" name="' . $field . '" id="' . $field . '" value="' . $this->getViewHtml($field, $value, $data) . '" class="span12 datepicker">';
    }

    public function getValue($field, $value, $data)
    {
        return !empty($value) ? date('Y-m-d', strtotime($value)) : null;
    }
}

class ImageField implements Field
{
    /**
     * Отображение значения из БД
     *
     * @param $field
     * @param $value
     * @param array $data
     * @return string
     */
    public function getViewHtml($field, $value, $data)
    {
        $filename = null;
        if (isset($data['id']) && $data['id'] != null) {
            $filename = str_replace('{id}', $data['id'], $data['sizes'][0]['filename']);
        }

        if ($filename != null && file_exists($filename)) {
            return '<img src="/' . $filename . '" class="thumbnail expandable">';
        }

        return '';
    }

    /**
     * Вывод редактора для поля
     *
     * @param $field
     * @param $value
     * @param array $data
     * @return mixed
     */
    public function getEditHtml($field, $value, $data)
    {
        $html = '<input type="file" name="' . $field . '" id="' . $field . '">';

        $filename = null;
        if (isset($data['id']) && $data['id'] != null) {
            $filename = str_replace('{id}', $data['id'], $data['sizes'][0]['filename']);
        }

        if ($filename != null && file_exists($filename)) {
            $html .= '<br><br><img src="/' . $filename . '" class="thumbnail expandable">';
        }

        return $html;
    }

    /**
     * Конвертирование из значения поля в значение БД
     *
     * @param $field
     * @param $value
     * @param array $data
     * @throws Exception
     * @return mixed
     */
    public function getValue($field, $value, $data)
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['size'] == 0) {
            return;
        }

        if (!isset($data['id']) || $data['id'] == null) {
            throw new Exception('Illegal state');
        }

        foreach ($data['sizes'] as $row) {

            $filename = str_replace('{id}', $data['id'], $row['filename']);

            $dir = dirname($filename);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            Image::init($_FILES[$field]['tmp_name'])
                ->resize(isset($row['width']) ? $row['width'] : null, isset($row['height']) ? $row['height'] : null)
                ->crop(isset($row['crop']) ? $row['crop'] : false)
                ->quality(isset($row['quality']) ? $row['quality'] : 90)
                ->thumbnail(true)
                ->save($filename);
        }
    }
}

class ReferenceField implements Field
{
    private $data;

    /**
     * Отображение значения из БД
     *
     * @param $field
     * @param $value
     * @param array $data
     * @return string
     */
    public function getViewHtml($field, $value, $data)
    {
        $data = $this->getData($field, $data);

        return isset($data[$value]) ? $data[$value] : '';
    }

    /**
     * Вывод редактора для поля
     *
     * @param $field
     * @param $value
     * @param array $data
     * @return mixed
     */
    public function getEditHtml($field, $value, $data)
    {
        $data = $this->getData($field, $data);

        $html = '<select name="' . $field . '"><option value=""></option>';

        foreach ($data as $k => $v) {
            $html .= '<option value="' . $k . '"' . ($k == $value ? ' selected="selected"' : '') . '>' . $v . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Конвертирование из значения поля в значение БД
     *
     * @param $field
     * @param $value
     * @param array $data
     * @return mixed
     */
    public function getValue($field, $value, $data)
    {
        return $value;
    }

    private function getData($field, $data)
    {
        $table = isset($data['table']) ? $data['table'] : $field;

        if (!isset($this->data[$field])) {
            $this->data[$field] = Database::instance()->getPairs('SELECT id, name FROM ' . $table . ' ORDER BY name');
        }

        return $this->data[$field];
    }
}