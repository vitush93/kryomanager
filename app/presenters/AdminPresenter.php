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
use Nette\Mail\SmtpException;

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
     * @param $order
     */
    private function finishHeliumOrder($order)
    {
        $this->setView('helium');

        $this->template->order = $order;

        $this['heliumOrderForm']->onSuccess[] = function (Form $form, $values) {
            $id = $this->getParameter('id');

            $this->orderManager->finishHeliumOrder($id, $values->weight);
        };
    }

    /**
     * @param $order
     */
    private function finishNitrogenOrder($order)
    {
        $this->orderManager->finishNitrogenOrder($order->id);
    }

    /**
     * @param $id
     */
    function actionFinish($id)
    {
        $order = $this->orderManager->find($id);
        if ($order->produkty_id == OrderManager::PRODUCT_NITROGEN) {
            $this->finishNitrogenOrder($order);

            $this->redirect($this->getParameter('ref'));
        } else if ($order->produkty_id == OrderManager::PRODUCT_HELIUM) {
            $this->finishHeliumOrder($order);
        }
    }

    /**
     * Mark order as completed.
     *
     * @param int $id
     */
    function actionComplete($id)
    {
        $order = $this->orderManager->find($id);

        if ($order->produkty_id == OrderManager::PRODUCT_HELIUM) {
            // TODO enter initial weight for helium
        }

        $previousOrderStatus = $order->objednavky_stav_id;
        $affected = $this->orderManager->completeConfirmedOrder($id);

        if ($affected > 0) {
            try {
                $this->notificationMailer
                    ->addTo($order->uzivatele->email)
                    ->setTemplateFile('notification.latte')
                    ->setSubject('Objednávka byla vyřízena!')
                    ->setTemplateVar('order', $order)
                    ->send();

                if (!$this->isAjax()) {
                    $this->flashMessage('Objednávka byla vyřízena.', 'success');
                }
            } catch (SmtpException $exception) {
                $this->flashMessage('E-mail se nepodařilo odeslat.', 'danger');

                // rollback order status
                $this->orderManager->setStatus($id, $previousOrderStatus);
            }
        }

        if (!$this->isAjax()) {
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
        $order = $this->orderManager->find($id);
        $previousOrderStatus = $order->objednavky_stav_id;
        $affected = $this->orderManager->cancelPendingOrder($id);

        if ($affected > 0) {
            try {
                $this->notificationMailer
                    ->addTo($order->uzivatele->email)
                    ->setTemplateFile('storno.latte')
                    ->setSubject('Objednávka byla stornována')
                    ->setTemplateVar('order', $order)
                    ->send();

                if (!$this->isAjax()) {
                    $this->flashMessage('Objednávka byla zrušena.', 'info');
                }
            } catch (SmtpException $exception) {
                $this->flashMessage('E-mail se nepodařilo odeslat.', 'danger');

                // rollback order status
                $this->orderManager->setStatus($id, $previousOrderStatus);
            }
        }

        if (!$this->isAjax()) {
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
     * @return Form
     */
    protected function createComponentHeliumOrderForm()
    {
        $form = new Form();

        $form->addText('weight', 'Váha po vrácení (kg)')
            ->addRule(Form::FLOAT, 'Zadejte platné číslo.')
            ->setRequired(FORM_REQUIRED);
        $form->addSubmit('process', 'Dokončit');;

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
}