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
        $affected = $this->orderManager->finishCompletedOrder($id, $volume ? $volume : 0);

        if (!$this->isAjax()) {
            if ($affected == 0) {
                $this->flashMessage("Objednávka č. $id neexistuje nebo nebyla označena jako vyřízená.", 'danger');
            } else {
                $this->flashMessage('Objednávka byla dokončena.', 'success');
            }

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
        $affected = $this->orderManager->completePendingOrder($id);

        if (!$this->isAjax()) {
            if ($affected == 0) {
                $this->flashMessage("Objednávka č. $id neexistuje nebo nebyla označena jako nevyřízená.", 'danger');
            } else {
                $this->flashMessage('Objednávka byla vyřízena.', 'success');
            }

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
        $ordersThisMonth = $this->orderManager->countOrders()
            ->where('MONTH(created) = MONTH(NOW())')
            ->where('YEAR(created) = YEAR(NOW())');

        $this->template->ordersCount = $ordersThisMonth->fetch()->count;
        $this->template->cancelsCount = $ordersThisMonth
            ->where('objednavky_stav_id', OrderManager::ORDER_STATUS_CANCELLED)
            ->fetch()
            ->count;

        $this->template->nitrogen = $this->orderManager->allOrders()
            ->select('SUM(objem) AS nitrogen')
            ->where('MONTH(created) = MONTH(NOW())')
            ->where('YEAR(created) = YEAR(NOW())')
            ->where('produkty_id', OrderManager::PRODUCT_NITROGEN)
            ->where('objednavky_stav_id', OrderManager::ORDER_STATUS_DONE)
            ->fetch()
            ->nitrogen;

        $this->template->helium = $this->orderManager->allOrders()
            ->select('SUM(objem) AS helium')
            ->where('MONTH(created) = MONTH(NOW())')
            ->where('YEAR(created) = YEAR(NOW())')
            ->where('produkty_id', OrderManager::PRODUCT_HELIUM)
            ->where('objednavky_stav_id', OrderManager::ORDER_STATUS_DONE)
            ->fetch()
            ->helium;

        $this->template->pending = $this->orderManager->getOrders()
            ->where('objednavky_stav_id IN (?)', [
                OrderManager::ORDER_STATUS_PENDING,
                OrderManager::ORDER_STATUS_CANCELLED
            ])
            ->order('created DESC')
            ->limit(10);
    }

    /**
     * @return Form
     */
    protected function createComponentCompleteOrderForm()
    {
        $form = new Form();

        $form->addText('obj_id', '#')
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

        $form->addText('obj_id', '#')
            ->addRule(Form::INTEGER, 'Zadejte celé číslo.')
            ->setRequired(FORM_REQUIRED);
        $form->addText('returned', 'Vráceno')
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