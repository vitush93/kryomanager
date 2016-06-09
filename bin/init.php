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

$container = require __DIR__ . '/../app/bootstrap.php';

/** @var \Nette\Database\Context $db */
$db = $container->getByType('Nette\Database\Context');

foreach ($instituce as $key => $item) {
    $db->table('instituce')->insert([
        'nazev' => $item
    ]);
}

foreach (array_keys($skupiny) as $inst) {
    foreach ($skupiny[$inst] as $sk) {
        $db->table('skupiny')->insert([
            'instituce_id' => $inst,
            'nazev' => $sk
        ]);
    }
}

foreach ($objednavky_stav as $key => $value) {
    $db->table('objednavky_stav')->insert([
        'nazev' => $value
    ]);
}

foreach ($nastaveni as $key => $value) {
    $db->table('nastaveni')->insert([
        'key' => $key,
        'value' => $value
    ]);
}