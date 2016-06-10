<?php

namespace App\Presenters;


use App\Model\OrderManager;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;

class AdminPresenter extends BasePresenter
{
    /** @var OrderManager @inject */
    public $orderManager;

    /**
     * Mark order as done.
     *
     * @param int $id
     * @param null|float $volume
     */
    function actionFinish($id, $volume = null)
    {
        $this->orderManager->finishCompletedOrder($id, $volume ? $volume : 0);

        if (!$this->isAjax()) {
            $this->flashMessage('Objednávka byla dokončena.', 'success');
            $this->redirect('default');
        }
    }

    /**
     * Mark order as completed.
     *
     * @param int $id
     */
    function actionComplete($id)
    {
        $this->orderManager->completePendingOrder($id);

        if (!$this->isAjax()) {
            $this->flashMessage('Objednávka byla vyřízena.', 'success');
            $this->redirect('default');
        }
    }

    /**
     * Mark order as cancelled.
     *
     * @param $id
     */
    function actionCancel($id)
    {
        $this->orderManager->cancelPendingOrder($id);

        if (!$this->isAjax()) {
            $this->flashMessage('Objednávka byla zrušena.', 'info');
            $this->redirect('default');
        }
    }

    function renderDefault()
    {
        $this->template->pending = $this->orderManager->getPendingOrders()
            ->order('id DESC')
            ->limit(10);
    }

    /**
     * @return Form
     */
    protected function createComponentCompleteOrderForm()
    {
        $form = new Form();

        $form->addText('obj_id', 'Číslo objednávky')
            ->addRule(Form::INTEGER, 'Zadejte celé číslo.')
            ->setRequired(FORM_REQUIRED);
        $form->addSubmit('process', 'Vyřídit');

        $form->onSuccess[] = function (Form $form, $values) {
            $this->actionComplete($values->obj_id);
        };

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @return Form
     */
    protected function createComponentDoneOrderForm()
    {
        $form = new Form();

        $form->addText('obj_id', 'Číslo objednávky')
            ->addRule(Form::INTEGER, 'Zadejte celé číslo.')
            ->setRequired(FORM_REQUIRED);
        $form->addText('returned', 'Vracený objem')
            ->addRule(Form::FLOAT, 'Zadejte číslo.')
            ->setRequired(FORM_REQUIRED)
            ->setDefaultValue(0);
        $form->addSubmit('process', 'Dokončit');

        $form->onSuccess[] = function (Form $form, $values) {
            $this->actionFinish($values->obj_id, $values->returned);
        };

        return BootstrapForm::makeBootstrap($form);
    }
}