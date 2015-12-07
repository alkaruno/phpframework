<?php

namespace Xplosio\PhpFramework;

abstract class Model
{
    private $errors;

    public function load($data)
    {
        $class = new \ReflectionClass($this);
        foreach ($class->getProperties() as $property) {
            if (!$property->isStatic()) {
                $name = String::toSnakeCase($property->getName());
                if (array_key_exists($name, $data)) {
                    $value = is_string($data[$name]) ? trim($data[$name]) : $data[$name];
                    if (!empty($data[$name])) {
                        $property->setAccessible(true);
                        $property->setValue($this, $value);
                    }
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

    public function validate($scenario = null)
    {
        $validator = $this->getValidator($scenario);
        if ($validator === null) {
            return true;
        }

        if ($validator->validate()) {
            return true;
        }

        $this->errors = $validator->getErrors();

        return false;
    }

    public function loadFromPostAndValidate($scenario = null)
    {
        return $this->loadFromPost() && $this->validate($scenario);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param null $scenario
     * @return Validator
     */
    abstract protected function getValidator($scenario);
}
