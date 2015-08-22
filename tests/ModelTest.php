<?php

require '../vendor/autoload.php';

use Xplosio\PhpFramework\Model;
use Xplosio\PhpFramework\Validator;

class ModelTest extends PHPUnit_Framework_TestCase
{
    public function testModel()
    {
        $model = new UserModel();

        $_SERVER['REQUEST_METHOD'] = 'GET';

        self::assertFalse($model->loadFromPost());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['username'] = ' root ';
        $_POST['password'] = ' password ';
        $_POST['first_name'] = 'John';
        $_POST['last_name'] = 'Doe';

        self::assertTrue($model->loadFromPost());

        self::assertEquals($model->username, 'root');
        self::assertEquals($model->password, 'password');
        self::assertEquals($model->firstName, 'John');
        self::assertEquals($model->getLastName(), 'Doe');

        self::assertTrue($model->validate());

        $_POST['username'] = '';
        self::assertFalse($model->loadFromPostAndValidate());

        $errors = $model->getErrors();
        self::assertTrue(is_array($errors) && count($errors) === 1 && array_key_exists('username', $errors) && $errors['username'] = 'Fill a username');
    }
}

class UserModel extends Model
{
    public $username;
    public $password;
    public $firstName;
    private $lastName;

    protected function getValidator()
    {
        $validator = new Validator();
        $validator->add('username')->required('Fill a username');

        return $validator;
    }

    public function getLastName()
    {
        return $this->lastName;
    }
}
