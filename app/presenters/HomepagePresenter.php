<?php

namespace App\Presenters;


use App\Forms\AccFormFactory;
use App\Forms\OrderFormFactory;
use App\Model\OrderManager;
use Grido\Components\Filters\Filter;
use Grido\Grid;
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
        $this->template->info = $this->accFormFactory->getUser();
    }

    /**
     * Cancel pending order.
     *
     * @param $id
     * @throws \Nette\Application\BadRequestException
     */
    function actionStorno($id)
    {
        if (!$this->orderManager->hasOrder($this->user->id, $id)) {
            $this->error();
        }

        $this->orderManager->cancelPendingOrder($id);

        // TODO notify admin

        $this->flashMessage('Objednávka byla zrušena.', 'info');
        $this->redirect('default');
    }

    protected function createComponentPendingOrders()
    {
        $grid = new Grid();
        $grid->setModel($this->orderManager->getPendingOrdersForUser($this->user->id));
        $grid->getTranslator()->setLang('cs');

        $grid->addColumnDate('created', 'Datum')->setSortable();
        $grid->addColumnText('produkt', 'Kryokapalina')->setSortable();
        $grid->addColumnNumber('objem', 'Objem')->setCustomRender(function ($item) {
            return $item->objem . ' litrů';
        })->setSortable();
        $grid->addColumnNumber('cena', 'Cena za litr')->setCustomRender(function ($item) {
            return $item->cena . ' Kč';
        })->setSortable();
        $grid->addColumnNumber('cena_celkem', 'Cena celkem')
            ->setCustomRender(function ($item) {
                return $item->cena_celkem . ' Kč';
            })->setSortable();
        $grid->addColumnNumber('dph', 'DPH')->setCustomRender(function ($item) {
            return $item->dph . ' %';
        })->setSortable();
        $grid->addColumnNumber('cena_celkem_dph', 'Cena celkem s DPH')->setCustomRender(function ($item) {
            return $item->cena_celkem_dph . ' Kč';
        })->setSortable();

        $grid->setFilterRenderType(Filter::RENDER_INNER);

        $grid->addActionHref('storno', 'Stornovat')
            ->setConfirm('Opravdu stornovat objednávku?')
            ->setIcon('remove');

        return $grid;
    }

    /**
     * @return Form
     */
    protected function createComponentAccForm()
    {
        $form = $this->accFormFactory->create();

        $form->onSuccess[] = function (Form $form) {
            if (!$form->hasErrors()) {
                $this->flashMessage('Změny byly uloženy . ', 'info');
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
