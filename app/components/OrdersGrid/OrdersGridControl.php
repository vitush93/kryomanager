<?php

namespace App\Controls;


use App\Model\OrderManager;
use Grido\Components\Filters\Filter;
use Grido\Grid;
use Nette\Application\UI\Control;
use Nette\Database\Context;
use Nette\Database\Table\Selection;

class OrdersGridControl extends Control
{
    /** @var Selection */
    private $model;

    /** @var Context */
    private $db;

    /**
     * OrdersGridControl constructor.
     * @param Context $context
     */
    function __construct(Context $context)
    {
        $this->db = $context;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    protected function createComponentGrid()
    {
        $grid = new Grid();
        $grid->setModel($this->model);
        $grid->getTranslator()->setLang('cs');
        $grid->setDefaultPerPage(50);
        $grid->setDefaultSort(['created' => 'DESC']);
        $grid->setExport('objednavky');

        $grid->addColumnNumber('id', '#')->setSortable();
        $grid->addColumnDate('created', 'Datum', 'j.n.Y H:i')->setSortable();
        $grid->addColumnText('produkt', 'Kryokapalina')->setSortable();
        $grid->addColumnText('jmeno', 'Jméno')->setSortable();
        $grid->addColumnText('instituce', 'Instituce')->setSortable();
        $grid->addColumnText('skupina', 'Skupina')->setSortable();
        $grid->addColumnNumber('objem', 'Objem')
            ->setCustomRender(function ($item) {
                return $item->objem . ' l';
            })->setSortable();
        $grid->addColumnNumber('cena_celkem', 'Cena celkem')
            ->setCustomRender(function ($item) {
                return $item->cena_celkem . ' Kč';
            })->setSortable();
        $grid->addColumnText('stav', 'Stav')->setSortable();

        $grid->setFilterRenderType(Filter::RENDER_INNER);

        $grid->addFilterNumber('id', '')
            ->setWhere(function ($val, Selection $selection) {
                $selection->where('objednavky.id = ?', $val);
            });
        $grid->addFilterText('jmeno', '')
            ->setWhere(function ($val, Selection $selection) {
                $selection->where('objednavky.jmeno LIKE ?', '%' . $val . '%');
            });

        $institucePairs = $this->db->table('instituce')->fetchPairs('id', 'nazev');
        $instituceConditions = [];
        foreach ($institucePairs as $id => $nazev) {
            $instituceConditions[$id] = ['instituce.id', '= ?', $id];
        }
        $institucePairs[] = ['' => 'vše'];
        $grid->addFilterSelect('instituce', '', $institucePairs)
            ->setDefaultValue('')
            ->setCondition($instituceConditions);

        $skupinyPairs = $this->db->table('skupiny')->fetchPairs('id', 'nazev');
        $skupinyConditions = [];
        foreach ($skupinyPairs as $id => $nazev) {
            $skupinyConditions[$id] = ['skupiny.id', '= ?', $id];
        }
        $skupinyPairs[] = ['' => 'vše'];
        $grid->addFilterSelect('skupina', '', $skupinyPairs)
            ->setDefaultValue('')
            ->setCondition($skupinyConditions);

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

        $grid->getColumn('id')->cellPrototype->class = 'right';
        $grid->getColumn('objem')->cellPrototype->class = 'right';
        $grid->getColumn('cena_celkem')->cellPrototype->class = 'right';

        $grid->addActionHref('cancel', 'Storno')
            ->setConfirm('Opravdu stornovat objednávku?')
            ->setIcon('remove')
            ->setCustomRender(function ($item) {
                if ($item->stav_id == OrderManager::ORDER_STATUS_PENDING) {
                    return '<a class="grid-action-cancel btn btn-default btn-xs btn-mini" href="'.$this->presenter->link('Admin:cancel', ['id' => $item->id, 'ref' => 'orders']).'" data-grido-confirm="Opravdu stornovat objednávku?"><i class="glyphicon glyphicon-remove fa fa-remove icon-remove"></i> Storno</a>';
                } else {
                    return '';
                }
            });
        $grid->addActionHref('complete', 'Vyřídit')
            ->setConfirm('Označit objednávku jako vyřízenou?')
            ->setIcon('ok')
            ->setCustomRender(function ($item) {
                if ($item->stav_id == OrderManager::ORDER_STATUS_PENDING) {
                    return '<a class="grid-action-complete btn btn-default btn-xs btn-mini" href="'.$this->presenter->link('Admin:complete', ['id' => $item->id, 'ref' => 'orders']).'" data-grido-confirm="Označit objednávku jako vyřízenou?"><i class="glyphicon glyphicon-ok fa fa-ok icon-ok"></i> Vyřídit</a>';
                } else {
                    return '';
                }
            });

        return $grid;
    }

    function render()
    {
        $this->template->setFile(__DIR__ . '/OrdersGrid.latte');

        $this->template->render();
    }
}

interface IOrdersGridControlFactory
{
    /** @return OrdersGridControl */
    function create();
}