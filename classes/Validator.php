<?php

class Validator
{
    private $data;
    private $autotrim;
    private $fields = array();
    private $errors = array();

    public function __construct($autotrim = true)
    {
        $this->autotrim = $autotrim;
    }

    public function add($field)
    {
        $validatorRule = new ValidatorRule();
        $this->fields[$field] = $validatorRule;
        return $validatorRule;
    }

    public function validate($data = null)
    {
        $data = $data != null ? $data : $_POST;

        if ($this->autotrim) {
            $this->data = array();
            foreach ($data as $name => $value) {
                $this->data[$name] = is_string($value) ? trim($value) : $value;
            }
        } else {
            $this->data = $data;
        }

        /**
         * @var ValidatorRule $validatorRule
         */
        foreach ($this->fields as $fieldName => $validatorRule) {

            $value = isset($this->data[$fieldName]) ? $this->data[$fieldName] : '';

            foreach ($validatorRule->getRules() as $rule) {

                if (isset($this->errors[$fieldName])) {
                    continue;
                }

                list($type, $error, $params) = $rule;

                if ($value == '' && $type != 'required' && !$validatorRule->hasCustomValidators()) {
                    continue;
                }

                if ($type == 'custom') {
                    $error = call_user_func_array($params[0], array($this->data[$fieldName]));
                    if ($error != null) {
                        $this->errors[$fieldName] = $error;
                    }
                } else {
                    if (call_user_func_array(array($this, 'validate' . ucfirst($type)), array($value, $params, $this->data)) !== true) {
                        $this->errors[$fieldName] = $error;
                    }
                }
            }
        }

        return count($this->errors) == 0;
    }

    public function getData($emptyToNull = true)
    {
        $data = $this->data;

        if ($emptyToNull) {
            foreach ($data as &$row) {
                if ($row == '') {
                    $row = null;
                }
            }
        }

        return $data;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /* validators: возвращают true, если ошибки нет */

    private function validateRequired($value, $params)
    {
        return $value != '';
    }

    private function validateLength($value, $params)
    {
        mb_internal_encoding('utf-8');
        $length = mb_strlen($value);

        if (count($params) != 2) {
            return false;
        }

        if ($length < $params[0] || $length > $params[1]) {
            return false;
        }

        return true;
    }

    private function validateRegexp($value, $params)
    {
        return preg_match('/' . $params[0] . '/u', $value) ? true : false;
    }

    private function validateMail($value, $params)
    {
        if ($params[0]) {
            $pattern = '/^([^@\\s]+)@((?:[-a-z0-9]+\\.)+[a-z]{2,})$/i';
        } else {
            $pattern = '/^([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-'
                    . '\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80-\\xff]|\\x5c\\x00-'
                    . '\\x7f)*\\x22)(\\x2e([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-'
                    . '\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80'
                    . '-\\xff]|\\x5c\\x00-\\x7f)*\\x22))*\\x40([^\\x00-\\x20\\x22\\x28\\x29'
                    . '\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x5b([^'
                    . '\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c\\x00-\\x7f)*\\x5d)(\\x2e([^\\x00-'
                    . '\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-'
                    . '\\xff]+|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c\\x00-\\x7f)*'
                    . '\\x5d))*$/';
        }
        return !preg_match($pattern, $value) ? false : true;
    }

    private function validateUnique($value, $params, $data)
    {
        $id = isset($data['id']) ? $data['id'] : null;

        return Db::getValue('SELECT COUNT(1) FROM `' . $params[0] . '` WHERE ' . ($id !== null ? ' id != ' . $id . ' AND ' : '') . ' `' . $params[1] . '` = ?', $value) == 0;
    }

    private function validateConfirm($value, $params, $data)
    {
        return $value == $data[$params[0]];
    }
}

class ValidatorRule
{
    private $rules = array();
    private $required = false;
    private $hasCustomValidators = false;

    public function required($error)
    {
        $this->rules[] = array('required', $error, array());
        $this->required = true;
        return $this;
    }

    public function length($min, $max, $error)
    {
        $this->rules[] = array('length', $error, array($min, $max));
        return $this;
    }

    public function min($size, $error)
    {
        return $this->length($size, null, $error);
    }

    public function max($size, $error)
    {
        return $this->length(null, $size, $error);
    }

    public function regexp($regex, $error)
    {
        $this->rules[] = array('regexp', $error, array($regex));
        return $this;
    }

    public function mail($error, $strict = false)
    {
        $this->rules[] = array('mail', $error, array($strict));
        return $this;
    }

    public function unique($table, $field, $error)
    {
        $this->rules[] = array('unique', $error, array($table, $field));
        return $this;
    }

    public function confirm($field, $error)
    {
        $this->rules[] = array('confirm', $error, array($field));
        return $this;
    }

    public function custom($handler)
    {
        $this->rules[] = array('custom', null, array($handler));
        $this->hasCustomValidators = true;
        return $this;
    }

    public function addRule($type, $error, $data)
    {
        $this->rules[] = array($type, $error, array($data));
    }

    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function hasCustomValidators()
    {
        return $this->hasCustomValidators;
    }
}