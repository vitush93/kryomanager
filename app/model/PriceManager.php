<?php

namespace App\Model;


use Nette\Database\Context;
use Nette\Object;
use Nette\Utils\DateTime;

class PriceManager extends Object
{
    const TABLE_PRICES = 'ceny';

    /** @var Context */
    private $db;

    /**
     * PriceManager constructor.
     * @param Context $context
     */
    function __construct(Context $context)
    {
        $this->db = $context;
    }

    /**
     * @return \Nette\Database\Table\Selection
     */
    function prices()
    {
        return $this->db->table(self::TABLE_PRICES);
    }

    /**
     * @param $cena
     * @param $instituceId
     * @param $produktId
     */
    function updatePrice($cena, $instituceId, $produktId)
    {
        // find last entry for this price
        $lastPriceId = $this->prices()
            ->where('instituce_id', $instituceId)
            ->where('produkty_id', $produktId)
            ->order('id DESC')
            ->fetch()
            ->id;

        // update validity for the old price
        $this->prices()
            ->where('id', $lastPriceId)
            ->update(['platna_do' => new DateTime('-1 second')]);

        // insert new price
        $this->prices()
            ->insert([
                'produkty_id' => $produktId,
                'instituce_id' => $instituceId,
                'cena' => $cena
            ]);
    }
}