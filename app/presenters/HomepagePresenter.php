<?php

namespace App\Presenters;


use App\Forms\OrderFormFactory;
use Nette\Application\UI\Form;

class HomepagePresenter extends BasePresenter
{
    /** @var OrderFormFactory @inject */
    public $orderFormFactory;

    /**
     * @return Form
     */
    protected function createComponentOrderForm()
    {
        return $this->orderFormFactory->create();
    }
}
