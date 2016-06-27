<?php

$container = require __DIR__ . '/../app/bootstrap.php';

/** @var \Nette\Database\Context $db */
$db = $container->getByType('Nette\Database\Context');

$admin = [
    'instituce_id' => 1,
    'skupiny_id' => 1,
    'email' => 'admin@example.com',
    'heslo' => \Nette\Security\Passwords::hash('admin123'),
    'jmeno' => 'Admin',
    'role' => 'admin'
];

$instituce = [
    1 => 'Externí',
    2 => 'MFF UK',
    3 => 'FÚ AVČR'
];

$skupiny = [
    1 => [
        'Externí'
    ],
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
    'vyřízená',
    'dokončená'
];

$nastaveni = [
    'dph.zadne' => '0',
    'dph.zakladni' => '21',
    'dph.prvni_snizena' => '15',
    'dph.druha_snizena' => '10',
    'faktura.jmeno' => 'Univerzita Karlova v Praze',
    'faktura.adresa' => "Nové Město, Ke Karlovu 3\n12116 Praha 2",
    'faktura.ico' => '00216208',
    'faktura.dic' => 'CZ00216208',
    'faktura.ucet' => '123456/0000',
    'smtp.addr' => 'admin@example.com',
    'smtp.host' => 'smtp.example.com',
    'smtp.username' => 'user',
    'smtp.password' => 'pass',
    'smtp.secure' => 'ssl'
];

$produkty = [
    1 => 'Helium',
    2 => 'Dusík'
];

$ceny = [
    // helium prices
    1 => [
        1 => [
            'cena' => '450'
        ],
        2 => [
            'cena' => '60'
        ],
        3 => [
            'cena' => '60'
        ]
    ],

    // nitrogen prices
    2 => [
        1 => [
            'cena' => '20'
        ],
        2 => [
            'cena' => '5'
        ],
        3 => [
            'cena' => '5'
        ]
    ]
];

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

if ($db->table('ceny')->count() == 0) {
    foreach ($ceny as $prod_id => $prices) {
        foreach ($prices as $inst_id => $values) {
            $values['instituce_id'] = $inst_id;
            $values['produkty_id'] = $prod_id;

            $db->table('ceny')->insert($values);
        }
    }
}

$checkAdmin = $db->table('uzivatele')->where('email', $admin['email'])->fetch();
if (!$checkAdmin) {
    $db->table('uzivatele')->insert($admin);
}