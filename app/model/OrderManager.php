<?php

namespace App\Model;


use Nette\Database\Context;
use Nette\Object;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class OrderManager extends Object
{
    const TABLE_ORDERS = 'objednavky';
    const TABLE_PRICES = 'ceny';

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