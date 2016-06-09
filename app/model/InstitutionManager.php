<?php
/**
 * Created by PhpStorm.
 * User: vitush
 * Date: 9.6.16
 * Time: 16:48
 */

namespace App\Model;


use Nette\Database\Context;

class InstitutionManager
{
    const INSTITUTION_TABLE_NAME = 'instituce',
        GROUP_TABLE_NAME = 'skupiny';
    
    private $db;
    
    function __construct(Context $context)
    {
        $this->db = $context;
    }

    function findInstitution($id) {
        return $this->db->table(self::INSTITUTION_TABLE_NAME)
            ->where('id', $id)
            ->fetch();
    }
    
    function findGroup($id) {
        return $this->db->table(self::GROUP_TABLE_NAME)
            ->where('id', $id)
            ->fetch();
    }
}