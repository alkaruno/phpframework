<?php

namespace Xplosio\PhpFramework;

use Swift_Attachment;
use Swift_Mailer;
use Swift_MailTransport;
use Swift_Message;

class Mailer
{
    private $subject;
    private $address;
    private $from;
    private $replyTo;

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

    public function setFrom($from)
    {
        $this->from = $from;
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
        if ($this->template !== null) {
            $body = App::render($this->template, $this->values, true);
        } else {
            $body = $this->text;
        }

        $message = new Swift_Message($this->subject, $body, 'text/html', 'UTF-8');
        $message->setFrom($this->from);
        if ($this->replyTo !== null) {
            $message->setReplyTo($this->replyTo);
        }

        foreach ($this->attachments as $filename) {
            $message->attach(Swift_Attachment::fromPath($filename));
        }

        $message->setTo($this->address);

        Swift_Mailer::newInstance(Swift_MailTransport::newInstance())->send($message);
    }
}
