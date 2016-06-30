<?php

namespace App\Model;


use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Object;

/**
 * Class SystemNotifications
 *
 * @package App\Model
 * @author Vit Habada
 */
class SystemNotifications extends Object
{
    const TABLE_NAME = 'upozorneni';

    /** @var Context */
    private $db;

    /**
     * SystemNotifications constructor.
     * @param Context $context
     */
    function __construct(Context $context)
    {
        $this->db = $context;
    }

    /**
     * @return Selection
     */
    function table()
    {
        return $this->db->table(self::TABLE_NAME);
    }

    /**
     * @return Selection
     */
    function getUnseen()
    {
        return $this->table()->where('seen', 0);
    }

    /**
     * @param $type
     * @param $text
     * @return bool|int|\Nette\Database\Table\IRow
     */
    function add($type, $text)
    {
        return $this->table()->insert([
            'typ' => $type,
            'text' => $text
        ]);
    }

    /**
     * @param int $id
     * @return int
     */
    function markAsSeen($id)
    {
        return $this->table()
            ->where('id', $id)
            ->update(['seen' => TRUE]);
    }
}