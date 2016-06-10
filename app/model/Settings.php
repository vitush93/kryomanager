<?php

namespace App\Model;


use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\Object;

class Settings extends Object
{
    const TABLE_SETTINGS = 'nastaveni';

    /** @var Context */
    private $db;

    /**
     * Settings constructor.
     * @param Context $context
     */
    function __construct(Context $context)
    {
        $this->db = $context;
    }

    /**
     * @param $key
     * @return Selection
     */
    private function _get($key)
    {
        return $this->db->table(self::TABLE_SETTINGS)
            ->where('key', $key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    function get($key)
    {
        return $this->_get($key)
            ->fetch()
            ->value;
    }

    /**
     * @param string $key
     * @param $value
     */
    function set($key, $value)
    {
        $this->_get($key)->update(['value' => $value]);
    }

    /**
     * @return array
     */
    function all()
    {
        return $this->db->table(self::TABLE_SETTINGS)
            ->fetchPairs('key', 'value');
    }
}