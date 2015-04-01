<?php

namespace Xplosio\PhpFramework;

use Xplosio\PhpFramework\App;
use Xplosio\PhpFramework\Logger;

class Mailer
{
    private $subject;
    private $address;
    private $sender;

    private $template;
    private $text;

    private $values = array();

    private $attachments = array();

    public static function newInstance()
    {
        return new Mailer();
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    public function set($name, $value)
    {
        $this->values[$name] = $value;
        return $this;
    }

    public function setValues($values)
    {
        foreach ($values as $name => $value) {
            $this->set($name, $value);
        }
        return $this;
    }

    public function send()
    {
        // TODO переписать и добавить работу с аттачами

        $headers = "MIME-Version: 1.0\n";
        $headers .= "Content-type: text/html; charset=utf-8\n";

        if ($this->sender != null) {
            $headers .= "From: " . $this->sender . "\n";
        }

        if ($this->template != null) {
            ob_start();
            App::showView($this->template, $this->values);
            $text = ob_get_clean();
        } else {
            $text = $this->text;
        }

        if (function_exists('mail')) {
            mail($this->address, $this->subject, $text, $headers);
        } else {
            Logger::info("Send mail: {$this->address}, subject: {$this->subject}, text:\n{$text}");
        }
    }
}