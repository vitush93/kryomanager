<?php

namespace App\Presenters;


use App\Controls\IDListControlFactory;
use App\Controls\IOrdersGridControlFactory;
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

    /** @var IOrdersGridControlFactory @inject */
    public $ordersGridControlFactory;

    /** @var IDListControlFactory @inject */
    public $dlistControlFactory;

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
     * @return \App\Controls\OrdersGridControl
     */
    protected function createComponentOrdersGrid()
    {
        $grid = $this->ordersGridControlFactory->create();

        $grid->setModel($this->orderManager->getOrders());

        return $grid;
    }

    /**
     * @return \App\Controls\DListControl
     */
    protected function createComponentDlist()
    {
        $control = $this->dlistControlFactory->create();

        return $control;
    }

    /**
     * @return Form
     */
    protected function createComponentDlistForm()
    {
        $form = new Form();

        $form->addText('obj_id', 'číslo objednávky')
            ->setRequired(FORM_REQUIRED);
        $form->addSubmit('process', 'Odeslat');

        $form->onSuccess[] = function (Form $form, $values) {
            $order = $this->orderManager->find($values->obj_id);
            if (!$order) {
                $form->addError('objednávka č. ' . $values->obj_id . ' nebyla nalezena.');

                return;
            }

            if ($order->objednavky_stav_id != OrderManager::ORDER_STATUS_COMPLETED) {
                $form->addError('objednávka č. ' . $values->obj_id . ' je označena jako ' . $order->objednavky_stav->nazev . ' - nelze tisknout dodací list.');
            }

            $this['dlist']->setOrder($order);

            $this->template->dlist = true;
        };

        return BootstrapForm::makeBootstrap($form);
    }
}