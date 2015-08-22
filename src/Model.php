<?php

namespace Xplosio\PhpFramework;

class Model
{
    private $errors;

    public function load($data)
    {
        $class = new \ReflectionClass($this);
        foreach ($class->getProperties() as $property) {
            if (!$property->isStatic()) {
                $name = String::toSnakeCase($property->getName());
                $value = is_string($data[$name]) ? trim($data[$name]) : $data[$name];
                $property->setAccessible(true);
                if (!empty($data[$name])) {
                    $property->setValue($this, $value);
                }
            }
        }
    }

    public function loadFromPost()
    {
        if (is_array($_POST) && Request::isPost()) {
            $this->load($_POST);
            return true;
        }

        return false;
    }

    public function validate()
    {
        $validator = $this->getValidator();
        if ($validator === null) {
            return true;
        }

        if ($validator->validate()) {
            return true;
        }

        $this->errors = $validator->getErrors();

        return false;
    }

    public function loadFromPostAndValidate()
    {
        return $this->loadFromPost() && $this->validate();
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return Validator
     */
    protected function getValidator()
    {
        return null;
    }
}
