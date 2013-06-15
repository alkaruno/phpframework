<?php

class FormValidator extends Validator
{
    const TOKEN_FORM_ATTRIBUTE = '_token';
    const TOKEN_SESSION_ATTRIBUTE = 'forms.token';

    public function validate()
    {
        if (isset($_POST[self::TOKEN_FORM_ATTRIBUTE]) && $_POST[self::TOKEN_FORM_ATTRIBUTE] != $_SESSION[self::TOKEN_SESSION_ATTRIBUTE]) {
            throw new Exception('Illegal access', 400);
        }

        return parent::validate($_POST);
    }
}