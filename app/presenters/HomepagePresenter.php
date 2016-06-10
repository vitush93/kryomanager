<?php

namespace App\Presenters;


use App\Forms\AccFormFactory;
use App\Forms\OrderFormFactory;
use App\Model\OrderManager;
use App\Model\UserManager;
use Grido\Components\Filters\Filter;
use Grido\Grid;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\Database\Table\Selection;
use Nette\Security\Passwords;

class HomepagePresenter extends BasePresenter
{
    /** @var OrderFormFactory @inject */
    public $orderFormFactory;

    /** @var AccFormFactory @inject */
    public $accFormFactory;

    /** @var OrderManager @inject */
    public $orderManager;

    protected function startup()
    {
        parent::startup();

        if ($this->user->isInRole(UserManager::ROLE_ADMIN)) {
            $this->redirect('Admin:default');
        }
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

    function renderDefault()
    {
        $this->template->ceny = $this->orderManager->getPricelistForUser($this->user->id);
        $this->template->info = $this->accFormFactory->getUser();
    }

    /**
     * @return Grid
     */
    protected function createComponentOrders()
    {
        $grid = new Grid();
        $grid->setModel($this->orderManager->getOrdersForUser($this->user->id));
        $grid->getTranslator()->setLang('cs');
        $grid->setDefaultPerPage(50);
        $grid->setExport('objednavky');

        $this->setupOrdersGrid($grid);
        $grid->addColumnText('stav', 'Stav')->setSortable();

        $grid->setFilterRenderType(Filter::RENDER_INNER);

        $grid->addFilterSelect('stav', '', [
            '' => '',
            'pending' => 'nevyřízená',
            'cancelled' => 'stornovaná',
            'completed' => 'vyřízená',
            'done' => 'dokončená'
        ])->setCondition([
            'pending' => ['objednavky_stav_id', '= ?', OrderManager::ORDER_STATUS_PENDING],
            'cancelled' => ['objednavky_stav_id', '= ?', OrderManager::ORDER_STATUS_CANCELLED],
            'completed' => ['objednavky_stav_id', '= ?', OrderManager::ORDER_STATUS_COMPLETED],
            'done' => ['objednavky_stav_id', '= ?', OrderManager::ORDER_STATUS_FINISHED]
        ]);

        $grid->addFilterSelect('produkt', '', [
            '' => '',
            1 => 'Helium',
            2 => 'Dusík'
        ])->setCondition([
            1 => ['objednavky.produkty_id', '= ?', 1],
            2 => ['objednavky.produkty_id', '= ?', 2]
        ]);

        $grid->addFilterDate('created', '')
            ->setWhere(function ($val, Selection $selection) {
                $date = new \DateTime($val);
                $selection->where('DATE(objednavky.created) = DATE(?)', $date);
            });

        return $grid;
    }

    /**
     * @return Grid
     * @throws \Grido\Exception
     */
    protected function createComponentPendingOrders()
    {
        $grid = new Grid();
        $grid->setModel($this->orderManager->getPendingOrdersForUser($this->user->id));
        $grid->getTranslator()->setLang('cs');

        $this->setupOrdersGrid($grid);

        $grid->setFilterRenderType(Filter::RENDER_INNER);

        $grid->addActionHref('storno', 'Stornovat')
            ->setConfirm('Opravdu stornovat objednávku?')
            ->setIcon('remove');

        return $grid;
    }

    private function setupOrdersGrid(Grid $grid)
    {
        $grid->addColumnDate('created', 'Datum')->setSortable();
        $grid->addColumnText('produkt', 'Kryokapalina');
        $grid->addColumnNumber('objem', 'Objem')->setCustomRender(function ($item) {
            return $item->objem . ' litrů';
        })->setSortable();
        $grid->addColumnNumber('cena', 'Cena za litr')->setCustomRender(function ($item) {
            return $item->cena . ' Kč';
        });
        $grid->addColumnNumber('cena_celkem', 'Cena celkem')
            ->setCustomRender(function ($item) {
                return $item->cena_celkem . ' Kč';
            })->setSortable();
        $grid->addColumnNumber('dph', 'DPH')->setCustomRender(function ($item) {
            return $item->dph . ' %';
        });
        $grid->addColumnNumber('cena_celkem_dph', 'Cena celkem s DPH')->setCustomRender(function ($item) {
            return $item->cena_celkem_dph . ' Kč';
        })->setSortable();
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
