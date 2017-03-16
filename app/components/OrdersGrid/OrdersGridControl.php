<?php

namespace App\Controls;


use App\Model\NotificationMail;
use App\Model\OrderManager;
use App\Model\SmtpMailer;
use Grido\Components\Filters\Filter;
use Grido\Grid;
use Nette\Application\UI\Control;
use Nette\Database\Context;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Mail\SmtpException;
use Nette\Utils\Html;

class OrdersGridControl extends Control
{
    /** @var Selection */
    private $model;

    /** @var Context */
    private $db;

    /** @var OrderManager */
    private $orderManager;

    /** @var SmtpMailer */
    private $smtpMailer;

    /** @var null|IRow */
    private $orderDetail = null;

    /** @var null|IRow */
    private $dlistData = null;

    /** @var IDListControlFactory */
    private $dlistControlFactory;

    /**
     * OrdersGridControl constructor.
     * @param Context $context
     * @param OrderManager $orderManager
     * @param IDListControlFactory $IDListControlFactory
     * @param SmtpMailer $smtpMailer
     */
    function __construct(Context $context, OrderManager $orderManager, IDListControlFactory $IDListControlFactory, SmtpMailer $smtpMailer)
    {
        parent::__construct();

        $this->db = $context;
        $this->orderManager = $orderManager;
        $this->dlistControlFactory = $IDListControlFactory;
        $this->smtpMailer = $smtpMailer;
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
        $grid->addColumnDate('datum_vyzvednuti', 'Datum vyzvednutí', 'j.n.Y H:i')->setSortable();
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
            'confirmed' => 'potvrzená',
            'cancelled' => 'stornovaná',
            'completed' => 'vyřízená',
            'done' => 'dokončená'
        ])->setCondition([
            'pending' => ['objednavky_stav_id', '= ?', OrderManager::ORDER_STATUS_PENDING],
            'confirmed' => ['objednavky_stav_id', '= ?', OrderManager::ORDER_STATUS_CONFIRMED],
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

        $grid->addActionHref('order', 'Detail')
            ->setCustomHref(function ($item) {
                return $this->link('detail!', $item->id);
            })
            ->setIcon('search');

        $grid->addActionHref('cancel', 'Storno')
            ->setConfirm('Opravdu stornovat objednávku?')
            ->setIcon('remove')
            ->setCustomRender(function ($item) {
                if ($item->stav_id == OrderManager::ORDER_STATUS_PENDING || $item->stav_id == OrderManager::ORDER_STATUS_CONFIRMED) {
                    return '<a class="grid-action-cancel btn btn-danger btn-xs btn-mini" href="' . $this->presenter->link('Admin:cancel', ['id' => $item->id, 'ref' => $this->presenter->name . ':' . $this->presenter->action]) . '" data-grido-confirm="Opravdu stornovat objednávku?"><i class="fa fa-remove"></i> Storno</a>';
                } else {
                    return '';
                }
            });

        $grid->addActionHref('finish', 'Dokončit')
            ->setCustomRender(function ($item) {
                if ($item->stav_id == OrderManager::ORDER_STATUS_COMPLETED) {
                    return '<a class="grid-action-complete btn btn-info btn-xs btn-mini" href="' . $this->presenter->link('Admin:finish', ['id' => $item->id, 'ref' => $this->presenter->name . ':' . $this->presenter->action]) . '" data-grido-confirm="Označit objednávku jako dokončenou?"><i class="fa fa-list"></i> Dokončit</a>';
                } else {
                    return '';
                }
            });

        $grid->addActionHref('complete', 'Vyřídit')
            ->setCustomRender(function ($item) {
                if ($item->stav_id == OrderManager::ORDER_STATUS_CONFIRMED) {
                    return '<a class="grid-action-complete btn btn-success btn-xs btn-mini" href="' . $this->presenter->link('Admin:complete', ['id' => $item->id, 'ref' => $this->presenter->name . ':' . $this->presenter->action]) . '" data-grido-confirm="Označit objednávku jako vyřízenou?"><i class="fa fa-check"></i> Vyřídit</a>';
                } else {
                    return '';
                }
            });

        $grid->addActionHref('confirm', 'Potvrdit')
            ->setCustomRender(function ($item) {
                if ($item->stav_id == OrderManager::ORDER_STATUS_PENDING) {
                    return '<a class="grid-action-complete btn btn-warning  btn-xs btn-mini" href="' . $this->link('confirm!', ['id' => $item->id]) . '" data-grido-confirm="Odeslat potvrzovací e-mail?"><i class="fa fa-envelope"></i> Potvrdit</a>';
                } else {
                    return '';
                }
            });

        return $grid;
    }

    /**
     * @param $id
     */
    function handlePending($id)
    {
        $this->orderManager->setStatus($id, OrderManager::ORDER_STATUS_PENDING);

        $this->handleDetail($id);
    }

    /**
     * @param $id
     */
    function handleCancel($id)
    {
        $this->orderManager->setStatus($id, OrderManager::ORDER_STATUS_CANCELLED);

        $this->handleDetail($id);
    }

    /**
     * @return NotificationMail
     */
    private function createNotificationMailer()
    {
        return new NotificationMail($this->createTemplate(), $this->smtpMailer);
    }

    /**
     * @param $id
     */
    function handleConfirm($id)
    {
        $affectedRowsCount = $this->orderManager->confirmPendingOrder($id);

        if ($affectedRowsCount > 0) {
            $notificationMailer = $this->createNotificationMailer();

            $order = $this->orderManager->find($id);

            try {
                $notificationMailer
                    ->addTo($order->uzivatele->email)
                    ->setTemplateFile('confirmation.latte')
                    ->setSubject('Objednávka potvrzena')
                    ->setTemplateVar('order', $order)
                    ->send();

                $this->presenter->flashMessage('E-mail odeslán.', 'info');
            } catch (SmtpException $exception) {
                $this->presenter->flashMessage('E-mail se nepodařilo odeslat.', 'danger');

                // error occurred - rollback status update
                $this->orderManager->setStatus($order->id, OrderManager::ORDER_STATUS_PENDING);
            }
        }

        $this->presenter->redirect('this');
    }

    /**
     * @param $id
     */
    function handleComplete($id)
    {
        $this->orderManager->setStatus($id, OrderManager::ORDER_STATUS_COMPLETED);

        $this->handleDetail($id);
    }

    /**
     * @param int $id
     */
    function handleDetail($id)
    {
        $this->orderDetail = $this->orderManager->find($id);
    }

    function handleBack()
    {
        $this->orderDetail = null;
        $this->dlistData = null;
    }

    function handleDlist($id)
    {
        $this->dlistData = $this->orderManager->find($id);
    }

    function render()
    {
        if ($this->orderDetail) {
            $this->template->o = $this->orderDetail;
            $this->template->setFile(__DIR__ . '/detail.latte');
        } else if ($this->dlistData) {
            $this->template->setFile(__DIR__ . '/dlist.latte');
        } else {
            $this->template->setFile(__DIR__ . '/OrdersGrid.latte');
        }

        $this->template->render();
    }

    protected function createComponentDlist()
    {
        $dlist = $this->dlistControlFactory->create();

        $dlist->setOrder($this->dlistData);

        return $dlist;
    }
}

interface IOrdersGridControlFactory
{
    /** @return OrdersGridControl */
    function create();
}