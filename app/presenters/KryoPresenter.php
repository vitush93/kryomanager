<?php

namespace App\Presenters;


use App\Model\NotificationMail;
use App\Model\OrderManager;
use App\Model\SmtpMailer;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\InvalidArgumentException;

class KryoPresenter extends BasePresenter
{
    /** @var OrderManager @inject */
    public $orderManager;

    /** @var SmtpMailer @inject */
    public $smtpMailer;

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

    public function finishOrderFormSucceeded(Form $form, $values)
    {
        try {
            $affected = $this->orderManager->finishOrder($values->obj_id, $values->returned);

            if ($affected == 0) {
                $this->flashMessage('Objednávku se nepodařilo dokončit (již je dokončená či stornovaná?).', 'warning');
            }
        } catch (InvalidArgumentException $e) {
            if (!$this->isAjax()) {
                $this->flashMessage($e->getMessage(), 'danger');
            }
        }

        $this->redirect('this');
    }

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

        $form->onSuccess[] = $this->finishOrderFormSucceeded;

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @param Form $form
     * @param $values
     */
    function completeOrderFormSucceeded(Form $form, $values)
    {
        $affected = $this->orderManager->completePendingOrder($values->obj_id);

        if ($affected > 0) {
            $order = $this->orderManager->find($values->obj_id);

            $this->notificationMailer
                ->addTo($order->uzivatele->email)
                ->setTemplateFile('notification.latte')
                ->setSubject('Objednávka byla vyřízena!')
                ->setTemplateVar('order', $order)
                ->send();

            $this->flashMessage('Objednávka byla vyřízena');
        } else {
            $this->flashMessage('Objednávku se nepodařilo dokončit (není označena jako nevyřízená?).', 'warning');
        }

        $this->redirect('this');
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

        $form->onSuccess[] = $this->completeOrderFormSucceeded;

        return BootstrapForm::makeBootstrap($form);
    }
}