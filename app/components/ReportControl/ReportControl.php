<?php

namespace App\Controls;


use App\Model\OrderManager;
use Nette\Application\UI\Control;
use Nette\Database\Table\Selection;

class ReportControl extends Control
{
    /** @var Selection */
    private $orders;

    /**
     * @return Selection
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param Selection $orders
     */
    public function setOrders($orders)
    {
        $this->orders = $orders;
    }

    private function calcReport()
    {
        $rep = $this->orders->select('
            produkty.nazev AS produkt,
            SUM(objem) AS volume,
            SUM(ceny.cena * objem) AS cost,
            SUM(ceny.cena * objem + ceny.cena * objem * dph /100) AS cost_dph
            ')
            ->group('objednavky.produkty_id')
            ->fetchAll();

        return $rep;
    }

    function render()
    {
        $this->template->setFile(__DIR__ . '/Report.latte');

        if ($this->orders) {
            $report = $this->calcReport();

            $this->template->report = $report;

            $this->template->render();
        }
    }
}

interface IReportControlFactory
{
    /** @return ReportControl */
    function create();
}