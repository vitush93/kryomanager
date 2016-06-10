<?php
/**
 * Created by PhpStorm.
 * User: vitush
 * Date: 9.6.16
 * Time: 16:48
 */

namespace App\Model;


use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

class InstitutionManager
{
    const INSTITUTION_TABLE_NAME = 'instituce',
        GROUP_TABLE_NAME = 'skupiny';

    /** @var Context */
    private $db;

    /**
     * InstitutionManager constructor.
     * @param Context $context
     */
    function __construct(Context $context)
    {
        $this->db = $context;
    }

    function getGroups()
    {
        return $this->db->table(self::GROUP_TABLE_NAME);
    }

    function getInstitutions()
    {
        return $this->db->table(self::INSTITUTION_TABLE_NAME);
    }

    function findInstitution($id)
    {
        return $this->getInstitutions()
            ->where('id', $id)
            ->fetch();
    }

    function findGroup($id)
    {
        return $this->getGroups()
            ->where('id', $id)
            ->fetch();
    }

    function groupPairs()
    {
        return $this->getGroups()
            ->order('nazev ASC')
            ->fetchPairs('id', 'nazev');
    }

    function institutionPairs()
    {
        return $this->getInstitutions()
            ->order('nazev ASC')
            ->fetchPairs('id', 'nazev');
    }

    function institutionPricelist()
    {
        $pricelist = [];

        /** @var ActiveRow $inst */
        foreach ($this->getInstitutions() as $inst) {
            $pricelist[$inst->id] = $inst->toArray();

            $now = new DateTime();
            $prices = $this->db->table('ceny')
                ->select('
                cena,
                produkty.nazev AS produkt,
                platna_od
                ')
                ->where('instituce_id', $inst->id)
                ->where('platna_od <= ?', $now)
                ->where('platna_do >= ? OR platna_do IS NULL', $now);
            foreach ($prices as $p) {
                $pricelist[$inst->id]['prices'][] = $p->toArray();
            }
        }

        return $pricelist;
    }
}