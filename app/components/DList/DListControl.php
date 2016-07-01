<?php

namespace App\Controls;


use App\Model\Settings;
use Nette\Application\UI\Control;
use Nette\Database\Table\IRow;

class DListControl extends Control
{
    /** @var IRow */
    private $order;

    /** @var Settings */
    private $settings;

    /**
     * DListControl constructor.
     * @param Settings $settings
     */
    function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    function setOrder($order)
    {
        $this->order = $order;
    }

    function render()
    {
        $this->template->setFile(__DIR__ . '/DList.latte');

        $this->template->settings = $this->settings;
        $this->template->order = $this->order;

        $this->template->render();
    }
}

interface IDListControlFactory
{
    /** @return DListControl */
    function create();
}