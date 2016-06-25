<?php

namespace App\Presenters;


use App\Controls\IReportControlFactory;
use App\Model\InstitutionManager;
use App\Model\OrderManager;
use App\Model\UserManager;
use DateTime;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;

class ReportingPresenter extends BasePresenter
{
    /** @var IReportControlFactory @inject */
    public $reportControlFactory;

    /** @var OrderManager @inject */
    public $orderManager;

    /** @var InstitutionManager @inject */
    public $institutionManager;

    protected function startup()
    {
        parent::startup();

        if (!$this->user->isInRole(UserManager::ROLE_ADMIN)) {
            $this->error();
        }
    }

    /**
     * @return array
     */
    private function orderYearsPairs()
    {
        return $years = $this->orderManager->allOrders()
            ->select('DISTINCT YEAR(created) AS yr')
            ->order('yr')
            ->fetchPairs('yr', 'yr');
    }

    /**
     * @return Form
     */
    protected function createComponentSkupinaForm()
    {
        $form = new Form();


        $form->addText('from', 'Od')
            ->setRequired(FORM_REQUIRED);
        $form->addText('to', 'Do')
            ->setRequired(FORM_REQUIRED);
        $form->addSelect('skupina', 'Skupina', $this->institutionManager->groupPairs())
            ->setPrompt('-vyberte-')
            ->setRequired(FORM_REQUIRED);
        $form->addSubmit('process', 'Odeslat');

        $form->onSuccess[] = function (Form $form, $values) {
            $orders = $this->orderManager->allOrders()
                ->where('objednavky.skupiny_id', $values->skupina)
                ->where('objednavky_stav_id', OrderManager::ORDER_STATUS_FINISHED)
                ->where('objednavky.created >= ?', new DateTime($values->from))
                ->where('objednavky.created <= ?', new DateTime($values->to));

            $this['report']->setOrders($orders);
        };

        $form = BootstrapForm::makeBootstrap($form);
        $form['from']->getControlPrototype()->class = 'form-control datepicker';
        $form['to']->getControlPrototype()->class = 'form-control datepicker';

        return $form;
    }

    /**
     * @return Form
     */
    protected function createComponentInstituceForm()
    {
        $form = new Form();

        $form->addText('from', 'Od')
            ->setRequired(FORM_REQUIRED);
        $form->addText('to', 'Do')
            ->setRequired(FORM_REQUIRED);
        $form->addSelect('instituce', 'Instituce', $this->institutionManager->institutionPairs())
            ->setPrompt('-vyberte-')
            ->setRequired(FORM_REQUIRED);
        $form->addSubmit('process', 'Odeslat');

        $form->onSuccess[] = function (Form $form, $values) {
            $orders = $this->orderManager->allOrders()
                ->where('objednavky.instituce_id', $values->instituce)
                ->where('objednavky_stav_id', OrderManager::ORDER_STATUS_FINISHED)
                ->where('objednavky.created >= ?', new DateTime($values->from))
                ->where('objednavky.created <= ?', new DateTime($values->to));

            $this['report']->setOrders($orders);
        };

        $form = BootstrapForm::makeBootstrap($form);
        $form['from']->getControlPrototype()->class = 'form-control datepicker';
        $form['to']->getControlPrototype()->class = 'form-control datepicker';

        return $form;
    }

    /**
     * @return \App\Controls\ReportControl
     */
    protected function createComponentReport()
    {
        $report = $this->reportControlFactory->create();

        return $report;
    }
}