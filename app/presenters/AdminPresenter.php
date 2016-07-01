<?php

namespace App\Presenters;


use App\Controls\IOrdersGridControlFactory;
use App\Model\InstitutionManager;
use App\Model\NotificationMail;
use App\Model\OrderManager;
use App\Model\PriceManager;
use App\Model\Settings;
use App\Model\SmtpMailer;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\InvalidArgumentException;

class AdminPresenter extends BasePresenter
{
    /** @var OrderManager @inject */
    public $orderManager;

    /** @var IOrdersGridControlFactory @inject */
    public $ordersGridControlFactory;

    /** @var InstitutionManager @inject */
    public $institutionManager;

    /** @var PriceManager @inject */
    public $priceManager;

    /** @var Settings @inject */
    public $settings;

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

            $ref = $this->getParameter('ref');
            if ($ref) {
                $this->redirect($ref);
            } else {
                $this->redirect('default');
            }
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
            ->where('objednavky_stav_id', OrderManager::ORDER_STATUS_FINISHED)
            ->fetch()
            ->nitrogen;

        $this->template->helium = $this->orderManager->allOrders()
            ->select('SUM(objem) AS helium')
            ->where('MONTH(created) = MONTH(NOW())')
            ->where('YEAR(created) = YEAR(NOW())')
            ->where('produkty_id', OrderManager::PRODUCT_HELIUM)
            ->where('objednavky_stav_id', OrderManager::ORDER_STATUS_FINISHED)
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

    function renderSettings()
    {
        $this->template->pricelist = $this->institutionManager->institutionPricelist();
    }

    /**
     * @param Form $form
     * @param $values
     */
    function smtpFormSucceeded(Form $form, $values)
    {
        foreach ($values as $key => $value) {
            $actualKey = "smtp.$key";
            $this->settings->set($actualKey, $value);
        }

        $this->flashMessage('Nastavení uloženo.', 'info');
        $this->redirect('this');
    }

    /**
     * @return Form
     */
    protected function createComponentSmtpForm()
    {
        $form = new Form();

        $form->addText('addr', 'E-mail')
            ->setDefaultValue($this->settings->get('smtp.addr'))
            ->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu.')
            ->setRequired(FORM_REQUIRED);
        $form->addText('host', 'Host')
            ->setDefaultValue($this->settings->get('smtp.host'))
            ->setRequired(FORM_REQUIRED);
        $form->addText('username', 'Uživatel')
            ->setDefaultValue($this->settings->get('smtp.username'))
            ->setRequired(FORM_REQUIRED);
        $form->addText('password', 'Heslo')
            ->setDefaultValue($this->settings->get('smtp.password'))
            ->setRequired(FORM_REQUIRED);
        $form->addSelect('secure', 'Zabezpečení', ['no' => 'žádné', 'ssl' => 'SSL', 'tls' => 'TLS'])
            ->setRequired(FORM_REQUIRED)
            ->setDefaultValue($this->settings->get('smtp.secure'));
        $form->addSubmit('process', 'Uložit');

        $form->onSuccess[] = $this->smtpFormSucceeded;

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @param Form $form
     * @param $values
     */
    function infoFormSucceeded(Form $form, $values)
    {
        foreach ($values as $key => $value) {
            $actualKey = "faktura.$key";
            $this->settings->set($actualKey, $value);
        }

        $this->flashMessage('Nastavení uloženo.', 'info');
        $this->redirect('this');
    }

    /**
     * @return Form
     */
    protected function createComponentInfoForm()
    {
        $form = new Form();

        $form->addText('uctarna', 'Účtárna')
            ->setDefaultValue($this->settings->get('faktura.uctarna'))
            ->setRequired(FORM_REQUIRED);
        $form->addText('jmeno', 'Jméno')
            ->setDefaultValue($this->settings->get('faktura.jmeno'))
            ->setRequired(FORM_REQUIRED);
        $form->addTextArea('adresa', 'Adresa')
            ->setDefaultValue($this->settings->get('faktura.adresa'))
            ->setRequired(FORM_REQUIRED);
        $form->addText('ico', 'IČO')
            ->setDefaultValue($this->settings->get('faktura.ico'))
            ->setRequired(FORM_REQUIRED);
        $form->addText('dic', 'DIČ')
            ->setDefaultValue($this->settings->get('faktura.dic'))
            ->setRequired(FORM_REQUIRED);
        $form->addText('ucet', 'Účet')
            ->setDefaultValue($this->settings->get('faktura.ucet'))
            ->setRequired(FORM_REQUIRED);
        $form->addSubmit('process', 'Uložit');

        $form->onSuccess[] = $this->infoFormSucceeded;

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @return Form
     */
    protected function createComponentPriceForm()
    {
        $form = new Form();

        $form->addSelect('instituce_id', 'Instituce', $this->institutionManager->institutionPairs())
            ->setPrompt('-vyberte-')
            ->setRequired(FORM_REQUIRED);
        $form->addSelect('produkty_id', 'Kryokapalina', $this->orderManager->productPairs())
            ->setPrompt('-vyberte-')
            ->setRequired(FORM_REQUIRED);
        $form->addText('cena', 'Cena')
            ->setRequired(FORM_REQUIRED)
            ->addRule(Form::FLOAT, 'Cena musí být číslo.');

        $form->addSubmit('process', 'Uložit');

        $form->onSuccess[] = $this->priceFormSucceeded;

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @param Form $form
     * @param $values
     */
    function priceFormSucceeded(Form $form, $values)
    {
        $this->priceManager->updatePrice($values->cena, $values->instituce_id, $values->produkty_id);

        $this->flashMessage('Změny byly uloženy', 'info');
        $this->redirect('this');
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