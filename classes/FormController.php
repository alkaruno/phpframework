<?php

abstract class FormController extends Controller
{
    public function handle()
    {
        if ($this->request->getMethod() == 'POST') {
            $validator = $this->getValidator();
            if ($validator->validate()) {
                $this->processForm($validator->getData());
                return $this->getSuccessView();
            } else {
                $this->request->set('errors', $validator->getErrors());
            }
        }

        $this->referenceData();

        return $this->getFormView();
    }

    /**
     * @return Validator
     */
    protected function getValidator()
    {
        ;
    }

    protected function referenceData()
    {
        ;
    }

    abstract protected function processForm($data);

    abstract protected function getFormView();

    abstract protected function getSuccessView();
}