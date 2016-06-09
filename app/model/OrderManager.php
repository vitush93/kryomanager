<?php

namespace App\Model;


use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Object;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class OrderManager extends Object
{
    const TABLE_ORDERS = 'objednavky';
    const TABLE_PRICES = 'ceny';

    const ORDER_STATUS_PENDING = 1,
        ORDER_STATUS_CANCELLED = 2,
        ORDER_STATUS_COMPLETED = 3;

    /** @var Context */
    private $db;

    /** @var Settings */
    private $settings;

    /** @var UserManager */
    private $userManager;

    /** @var InstitutionManager */
    private $institutionManager;

    /**
     * OrderManager constructor.
     * @param Context $context
     * @param InstitutionManager $institutionManager
     * @param Settings $settings
     * @param UserManager $userManager
     */
    function __construct(Context $context,
                         InstitutionManager $institutionManager,
                         Settings $settings,
                         UserManager $userManager)
    {
        $this->db = $context;
        $this->userManager = $userManager;
        $this->settings = $settings;
        $this->institutionManager = $institutionManager;
    }

    /**
     * @param $id
     * @return Selection
     */
    private function order($id)
    {
        return $this->db->table(self::TABLE_ORDERS)
            ->where('id', $id);
    }

    /**
     * @param int $id
     */
    function cancelPendingOrder($id)
    {
        $this->order($id)
            ->where('objednavky_stav_id', self::ORDER_STATUS_PENDING)
            ->update(['objednavky_stav_id' => self::ORDER_STATUS_CANCELLED]);
    }

    /**
     * Check if order belongs to a user.
     *
     * @param int $user_id
     * @param int $order_id
     * @return bool
     */
    function hasOrder($user_id, $order_id)
    {
        $order = $this->order($order_id)
            ->where('uzivatele_id', $user_id)
            ->fetch();

        return $order !== false;
    }

    /**
     * @return Selection
     */
    function getOrders()
    {
        return $this->db->table(self::TABLE_ORDERS)
            ->select('
            objednavky.id AS id,
            objednavky.created AS created, 
            objednavky.objem AS objem, 
            objednavky.objem_vraceno AS objem_vraceno, 
            objednavky.jmeno AS jmeno, 
            objednavky.dph AS dph,
            ceny.cena AS cena,
            (cena * objem) AS cena_celkem,
            (cena * objem + cena * objem * objednavky.dph/100) AS cena_celkem_dph,
            produkty.nazev AS produkt,
            uzivatele.email AS email,
            skupiny.nazev AS skupina,
            instituce.nazev AS intituce,
            objednavky_stav.nazev AS stav');
    }

    /**
     * @param $user_id
     * @return Selection
     */
    function getPendingOrdersForUser($user_id)
    {
        return $this->getOrders()
            ->where('uzivatele_id', $user_id)
            ->where('objednavky_stav_id', self::ORDER_STATUS_PENDING);
    }

    /**
     * @param int $user_id
     * @return array|\Nette\Database\Table\IRow[]
     */
    function getPricelistForUser($user_id)
    {
        $user = $this->userManager->find($user_id);
        $now = new DateTime();

        return $this->db->table(OrderManager::TABLE_PRICES)
            ->where('instituce_id', $user->instituce_id)
            ->where('platna_od <= ?', $now)
            ->where('platna_do >= ? OR platna_do IS NULL', $now)
            ->fetchAll();
    }

    /**
     * @param int $product_id
     * @param float $volume amount of kryoliquid
     * @param int $user_id
     */
    function add($product_id, $volume, $user_id)
    {
        $objednavka = new ArrayHash();

        // objem, produkt, uzivatel
        $objednavka->objem = $volume;
        $objednavka->produkty_id = $product_id;
        $objednavka->uzivatele_id = $user_id;

        // jmeno, skupina, instituce
        $user = $this->userManager->find($user_id);
        $objednavka->jmeno = $user->jmeno;
        $objednavka->skupiny_id = $user->skupiny_id;
        $objednavka->instituce_id = $user->instituce_id;

        // get price id
        $now = new DateTime();
        $objednavka->ceny_id = $this->db->table(self::TABLE_PRICES)
            ->where('produkty_id', $product_id)
            ->where('instituce_id', $user->instituce_id)
            ->where('platna_od <= ?', $now)
            ->where('platna_do >= ? OR platna_do IS NULL', $now)
            ->fetch()->id;

        // get dph percentage
        $dph_config_key = $this->institutionManager
            ->findInstitution($user->instituce_id)
            ->dph;
        $dph = $this->settings->get($dph_config_key);
        $objednavka->dph = $dph;

        $this->db->table(self::TABLE_ORDERS)->insert($objednavka);
    }
}