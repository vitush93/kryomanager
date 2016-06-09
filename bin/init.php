<?php

$instituce = [
    1 => 'Externí',
    2 => 'MFF UK',
    3 => 'FÚ AVČR'
];

$skupiny = [
    2 => [
        'KFNT',
        'KFKL',
        'KFM',
        'KFPP'
    ],
    3 => [
        'ÚACh'
    ]
];

$objednavky_stav = [
    'nevyřízená',
    'stornovaná',
    'vyřízená'
];

$nastaveni = [
    'dph.zadne' => '0',
    'dph.zakladni' => '21',
    'dph.prvni_snizena' => '15',
    'dph.druha_snizena' => '10'
];

$produkty = [
    'Helium',
    'Dusík'
];

$container = require __DIR__ . '/../app/bootstrap.php';

/** @var \Nette\Database\Context $db */
$db = $container->getByType('Nette\Database\Context');

if ($db->table('instituce')->count() == 0) {
    foreach ($instituce as $key => $item) {
        $db->table('instituce')->insert([
            'nazev' => $item
        ]);
    }
}

if ($db->table('skupiny')->count() == 0) {
    foreach (array_keys($skupiny) as $inst) {
        foreach ($skupiny[$inst] as $sk) {
            $db->table('skupiny')->insert([
                'instituce_id' => $inst,
                'nazev' => $sk
            ]);
        }
    }
}

if ($db->table('objednavky_stav')->count() == 0) {
    foreach ($objednavky_stav as $key => $value) {
        $db->table('objednavky_stav')->insert([
            'nazev' => $value
        ]);
    }
}

if ($db->table('nastaveni')->count() == 0) {
    foreach ($nastaveni as $key => $value) {
        $db->table('nastaveni')->insert([
            'key' => $key,
            'value' => $value
        ]);
    }
}

if ($db->table('produkty')->count() == 0) {
    foreach ($produkty as $p) {
        $db->table('produkty')->insert([
            'nazev' => $p
        ]);
    }
}