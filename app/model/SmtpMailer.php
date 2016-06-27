<?php

namespace App\Model;

/**
 * Class SmtpMailer
 * @package App\Model
 */
class SmtpMailer
{
    /** @var \Nette\Mail\SmtpMailer */
    private $instance;

    /**
     * SmtpMailer constructor.
     * @param Settings $settings
     */
    function __construct(Settings $settings)
    {
        $this->instance = new \Nette\Mail\SmtpMailer([
            'host' => $settings->get('smtp.host'),
            'username' => $settings->get('smtp.username'),
            'password' => $settings->get('smtp.password'),
            'secure' => $settings->get('smtp.secure')
        ]);
    }

    /**
     * @return \Nette\Mail\SmtpMailer
     */
    function getMailer()
    {
        return $this->instance;
    }

}