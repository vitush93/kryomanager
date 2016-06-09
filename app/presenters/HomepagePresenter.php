<?php

namespace App\Presenters;


use App\Forms\OrderFormFactory;

class HomepagePresenter extends BasePresenter
{
    /** @var OrderFormFactory @inject */
    public $orderFormFactory;

    protected function createComponentOrderForm()
    {
        return $this->orderFormFactory->create(function () {
            $this->redirect('this');
        });
    }
}
