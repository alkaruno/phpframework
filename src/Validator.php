<?php

namespace Xplosio\PhpFramework;

class Validator
{
    const TYPE_REQUIRED = 'required';
    const TYPE_LENGTH = 'length';
    const TYPE_REGEXP = 'regexp';
    const TYPE_INT = 'int';
    const TYPE_MAIL = 'mail';
    const TYPE_UNIQUE = 'unique';
    const TYPE_CONFIRM = 'confirm';
    const TYPE_IN = 'in';

    const TYPE_CUSTOM = 'custom';

    private $data;
    private $autotrim;

    private $fields = [];
    private $errors = [];

    public function __construct($autotrim = true)
    {
        $this->autotrim = $autotrim;
    }

    public function add($field)
    {
        $validatorRule = new ValidatorRules();
        $this->fields[$field] = $validatorRule;
        return $validatorRule;
    }

    public function validate(array $data = null)
    {
        mb_internal_encoding('utf-8');

        $this->data = $data !== null ? $data : $_POST;
        $this->errors = [];

        if ($this->autotrim) {
            $this->data = array_map(function ($value) {
                return is_string($value) ? trim($value) : $value;
            }, $this->data);
        }

        $validators = $this->getValidators();

        /**
         * @var ValidatorRules $rules
         */
        foreach ($this->fields as $fieldName => $rules) {

            $value = array_key_exists($fieldName, $this->data) ? $this->data[$fieldName] : '';

            foreach ($rules->getRules() as $rule) {

                list($type, $error, $params) = $rule;

                if ($value === '' && $type !== self::TYPE_REQUIRED && !$rules->isHasCustomValidator()) {
                    continue;
                }

                if ($type === self::TYPE_CUSTOM) {
                    $error = call_user_func_array($params[0], [$value]);
                    if ($error !== null) {
                        $this->errors[$fieldName] = $error;
                        break;
                    }
                } else {

                    $validator = array_key_exists($type, $validators) ? $validators[$type] : null;
                    Assert::notNull($validator, 'Not found validator for type: ' . $type);

                    if (call_user_func_array($validator, [$value, $params, $this->data]) !== true) {
                        $this->errors[$fieldName] = $error;
                        break;
                    }
                }
            }
        }

        return count($this->errors) === 0;
    }

    public function getData($emptyToNull = true)
    {
        return array_map(function ($value) use ($emptyToNull) {
            return $value === '' && $emptyToNull ? null : $value;
        }, $this->data);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function getValidators()
    {
        return [
            self::TYPE_REQUIRED => function ($value) {
                return $value !== '';
            },
            self::TYPE_LENGTH => function ($value, $params) {
                $length = mb_strlen($value);
                return $length >= $params[0] && $length <= $params[1];
            },
            self::TYPE_REGEXP => function ($value, $params) {
                return preg_match('/' . $params[0] . '/u', $value) ? true : false;
            },
            self::TYPE_INT => function ($value) {
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            },
            self::TYPE_MAIL => function ($value) {
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            },
            self::TYPE_UNIQUE => function ($value, $params, $data) {
                $id = array_key_exists('id', $data) ? $data['id'] : 0;
                return Db::getValue("SELECT COUNT(1) FROM `$params[0]` WHERE id != ? && `$params[1]` = ?", $id, $value) === 0;
            },
            self::TYPE_CONFIRM => function ($value, $params, $data) {
                return $value === $data[$params[0]];
            },
            self::TYPE_IN => function ($value, $params) {
                list($values, $strict) = $params;
                return in_array($value, (array)$values, $strict);
            }
        ];
    }
}

class ValidatorRules
{
    private $rules = [];
    private $required = false;
    private $hasCustomValidator = false;

    public function required($error)
    {
        $this->rules[] = [Validator::TYPE_REQUIRED, $error, []];
        $this->required = true;
        return $this;
    }

    public function length($min, $max, $error)
    {
        $this->rules[] = [Validator::TYPE_LENGTH, $error, [$min, $max]];
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

    public function int($error)
    {
        $this->rules[] = [Validator::TYPE_INT, $error, []];
        return $this;
    }

    public function regexp($regex, $error)
    {
        $this->rules[] = [Validator::TYPE_REGEXP, $error, [$regex]];
        return $this;
    }

    public function mail($error, $strict = false)
    {
        $this->rules[] = [Validator::TYPE_MAIL, $error, [$strict]];
        return $this;
    }

    public function unique($table, $field, $error)
    {
        $this->rules[] = [Validator::TYPE_UNIQUE, $error, [$table, $field]];
        return $this;
    }

    public function confirm($field, $error)
    {
        $this->rules[] = [Validator::TYPE_CONFIRM, $error, [$field]];
        return $this;
    }

    public function in($values, $error, $strict = false)
    {
        $this->rules[] = [Validator::TYPE_IN, $error, [$values, $strict]];
        return $this;
    }

    public function custom($handler)
    {
        $this->rules[] = [Validator::TYPE_CUSTOM, null, [$handler]];
        $this->hasCustomValidator = true;
        return $this;
    }

    /** @deprecated */
    public function addRule($type, $error, $data)
    {
        $this->rules[] = [$type, $error, [$data]];
    }

    /** @deprecated */
    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function isHasCustomValidator()
    {
        return $this->hasCustomValidator;
    }
}