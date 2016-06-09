<?php

namespace App\Presenters;


use App\Forms\AccFormFactory;
use App\Forms\OrderFormFactory;
use App\Model\OrderManager;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;

class HomepagePresenter extends BasePresenter
{
    /** @var OrderFormFactory @inject */
    public $orderFormFactory;

    /** @var AccFormFactory @inject */
    public $accFormFactory;

    /** @var OrderManager @inject */
    public $orderManager;

    function renderDefault()
    {
        $this->template->ceny = $this->orderManager->getPricelistForUser($this->user->id);
    }

    /**
     * @return Form
     */
    protected function createComponentAccForm()
    {
        $form = $this->accFormFactory->create();

        $form->onSuccess[] = function (Form $form) {
            if (!$form->hasErrors()) {
                $this->flashMessage('Změny byly uloženy.', 'info');
                $this->redirect('this');
            }
        };

        return $form;
    }

    /**
     * @return Form
     */
    protected function createComponentOrderForm()
    {
        return $this->orderFormFactory->create();
    }
}
