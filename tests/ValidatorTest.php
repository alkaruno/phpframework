<?php

use Xplosio\PhpFramework\Validator;

class ValidatorTest extends PHPUnit_Framework_TestCase
{
    const AMOUNT = 'amount';
    const MAIL = 'mail';

    /** @var Validator */
    private $validator;

    public static function setUpBeforeClass()
    {
        require '../vendor/autoload.php';
    }

    public function testValidator()
    {
        $this->validator = new Validator();
        $this->validator->add(self::AMOUNT)->required('Required')->int('Number');
        $this->validator->add(self::MAIL)->mail('Mail');

        $data = [
            self::AMOUNT => '',
            self::MAIL => ''
        ];

        $this->validateData($data, false, [self::AMOUNT => 'Required']);

        $data[self::AMOUNT] = '0100';
        $this->validateData($data, false, [self::AMOUNT => 'Number']);

        $data[self::AMOUNT] = '100';
        $this->validateData($data, true, []);

        $data[self::MAIL] = 'alkaruno.gmail.com';
        $this->validateData($data, false, [self::MAIL => 'Mail']);

        $data[self::MAIL] = 'alkaruno@gmail.com';
        $this->validateData($data, true, []);
    }

    public function testInValidation()
    {
        $this->validator = new Validator();
        $this->validator->add(self::AMOUNT)->in([100, 200], self::AMOUNT, true);

        $this->validateData([self::AMOUNT => 100], true, []);
        $this->validateData([self::AMOUNT => 200], true, []);
        $this->validateData([self::AMOUNT => 150], false, [self::AMOUNT => self::AMOUNT]);
        $this->validateData([self::AMOUNT => '100'], false, [self::AMOUNT => self::AMOUNT]);

        $this->validator = new Validator();
        $this->validator->add(self::AMOUNT)->in(100, self::AMOUNT, false);

        $this->validateData([self::AMOUNT => 100], true, []);
        $this->validateData([self::AMOUNT => '100'], true, []);
        $this->validateData([self::AMOUNT => 200], false, [self::AMOUNT => self::AMOUNT]);
    }

    private function validateData($data, $success, $errors)
    {
        if ($success) {
            self::assertTrue($this->validator->validate($data));
        } else {
            self::assertFalse($this->validator->validate($data));
        }

        self::assertEquals($this->validator->getErrors(), $errors);
    }
}
