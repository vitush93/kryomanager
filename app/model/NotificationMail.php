<?php

namespace App\Model;


use Nette\Application\UI\ITemplate;
use Nette\Mail\Message;

class NotificationMail
{
    const TEMPLATE_PATH = WWW_DIR . '/../app/presenters/templates/mail/';

    /** @var ITemplate */
    public $template;

    /** @var Message */
    private $mail;

    /** @var  SmtpMailer */
    private $mailer;

    function __construct(ITemplate $template, SmtpMailer $mailer)
    {
        $this->mailer = $mailer;
        $this->mail = new Message();
        $this->template = $template;
    }

    function setSubject($subject)
    {
        $this->mail->setSubject($subject);

        return $this;
    }

    function send()
    {
        $this->mail->setHtmlBody($this->template);

        $this->mailer->getMailer()->send($this->mail);
    }

    function addTo($email)
    {
        $this->mail->addTo($email);

        return $this;
    }

    function setTemplateFile($templateFile)
    {
        $this->template->setFile(self::TEMPLATE_PATH . $templateFile);

        return $this;
    }

    function setTemplateVar($name, $value)
    {
        $this->template->{$name} = $value;

        return $this;
    }
}