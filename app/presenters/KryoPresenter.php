<?php

namespace App\Presenters;


use App\Model\NotificationMail;
use App\Model\OrderManager;
use App\Model\Settings;
use App\Model\SmtpMailer;
use App\Model\SystemNotifications;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\InvalidArgumentException;
use Nette\Utils\DateTime;

class KryoPresenter extends BasePresenter
{
    /** @var OrderManager @inject */
    public $orderManager;

    /** @var SmtpMailer @inject */
    public $smtpMailer;

    /** @var SystemNotifications @inject */
    public $systemNotifications;

    /** @var Settings @inject */
    public $settings;

    /** @var NotificationMail */
    private $notificationMailer;

    /**
     * Prepare mailer object.
     */
    protected function startup()
    {
        parent::startup();

        $this->notificationMailer = new NotificationMail($this->createTemplate(), $this->smtpMailer);
    }

    /**
     * Mark order as done.
     *
     * @param int $id
     * @param null|float $volume
     */
    function actionFinish($id, $volume = null)
    {
        try {
            $affected = $this->orderManager->finishOrder($id, $volume ? $volume : 0);
        } catch (InvalidArgumentException $e) {
            if (!$this->isAjax()) {
                $this->flashMessage($e->getMessage(), 'danger');
            }

            return;
        }

        $order = $this->orderManager->find($id);
        $this->notificationMailer
            ->addTo($this->settings->get('faktura.uctarna'))
            ->setTemplateFile('invoice.latte')
            ->setSubject('Faktura')
            ->setTemplateVar('order', $order)
            ->send();

        if (!$this->isAjax()) {
            if ($affected == 0) {
                $this->flashMessage("Objednávka č. $id neexistuje nebo byla stornovaná či je již dokončená.", 'danger');
            } else {
                $this->flashMessage('Objednávka byla dokončena.', 'success');
            }

            $this->redirect('this');
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

        if ($affected > 0) {
            $order = $this->orderManager->find($id);
            $this->notificationMailer
                ->addTo($order->uzivatele->email)
                ->setTemplateFile('notification.latte')
                ->setSubject('Objednávka byla vyřízena!')
                ->setTemplateVar('order', $order)
                ->send();
        }

        if (!$this->isAjax()) {
            if ($affected == 0) {
                $this->flashMessage("Objednávka č. $id neexistuje, nebyla označena jako nevyřízená nebo byla stornovaná.", 'danger');
            } else {
                $this->flashMessage('Objednávka byla vyřízena.', 'success');
            }

            $ref = $this->getParameter('ref');
            if ($ref) {
                $this->redirect($ref);
            } else {
                $this->redirect('default');
            }
        }
    }

    /**
     * Mark order as cancelled.
     *
     * @param $id
     */
    function actionCancel($id)
    {
        $affected = $this->orderManager->cancelPendingOrder($id);

        if (!$this->isAjax()) {
            if ($affected == 0) {
                $this->flashMessage("Objednávka č. $id neexistuje nebo nebyla označena jako nevyřízená.", 'danger');
            } else {
                $this->flashMessage('Objednávka byla zrušena.', 'info');
            }

            $ref = $this->getParameter('ref');
            if ($ref) {
                $this->redirect($ref);
            } else {
                $this->redirect('default');
            }
        }
    }

    /**
     * @param int $id
     */
    function handleSeen($id)
    {
        $this->systemNotifications->markAsSeen($id);

        $this->redirect('this');
    }

    function renderDefault()
    {
        $this->template->today = $this->orderManager->getPendingOrders()
            ->where('DATE(datum_vyzvednuti) = DATE(?)', new DateTime())
            ->order('created DESC')
            ->limit(10);

        $this->template->tomorrow = $this->orderManager->getPendingOrders()
            ->where('DATE(datum_vyzvednuti) = DATE(?)', new DateTime('tomorrow'))
            ->order('created DESC')
            ->limit(10);

        $this->template->notifications = $this->systemNotifications->getUnseen()
            ->limit(10);
    }

    /**
     * @return Form
     */
    protected function createComponentFinishOrderForm()
    {
        $form = new Form();

        $form->addText('obj_id', '#')
            ->setOption('description', 'Číslo objednávky.')
            ->addRule(Form::INTEGER, 'Zadejte celé číslo.')
            ->setRequired(FORM_REQUIRED);
        $form->addText('returned', 'Vráceno')
            ->setOption('description', 'Vrácený objem v litrech.')
            ->addRule(Form::FLOAT, 'Zadejte číslo.')
            ->setRequired(FORM_REQUIRED)
            ->setDefaultValue(0);
        $form->addSubmit('process', 'Dokončit');

        $form->onSuccess[] = function (Form $form, $values) {
            $this->actionFinish($values->obj_id, $values->returned);
        };

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @return Form
     */
    protected function createComponentCompleteOrderForm()
    {
        $form = new Form();

        $form->addText('obj_id', '#')
            ->setOption('description', 'Číslo objednávky.')
            ->addRule(Form::INTEGER, 'Zadejte celé číslo.')
            ->setRequired(FORM_REQUIRED);
        $form->addSubmit('process', 'Vyřídit');

        $form->onSuccess[] = function (Form $form, $values) {
            $this->actionComplete($values->obj_id);
        };

        return BootstrapForm::makeBootstrap($form);
    }
}