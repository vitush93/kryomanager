<?php


namespace App\Forms;


use Nette\Application\UI\Form;
use Nette\Object;

class OrderFormFactory extends Object
{
    function create(callable $onSuccess = null)
    {
        $form = new Form();

        // TODO order form
        
        return $form;
    }
}