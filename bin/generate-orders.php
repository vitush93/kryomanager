<?php

$container = require __DIR__ . '/../app/bootstrap.php';

/** @var \Nette\Database\Context $db */
$db = $container->getByType('Nette\Database\Context');


foreach ($db->table('uzivatele')->where('role', 'user') as $user) {
    $num_orders = rand(10, 100);

    for ($i = 0; $i < $num_orders; $i++) {
        $prod_id = rand(1, 2);

        $cena = $db->table('ceny')
            ->where('produkty_id', $prod_id)
            ->where('instituce_id', $user->instituce_id)
            ->fetch();

        $db->table('objednavky')->insert([
            'ceny_id' => $cena->id,
            'produkty_id' => $prod_id,
            'uzivatele_id' => $user->id,
            'skupiny_id' => $user->skupiny_id,
            'instituce_id' => $user->instituce_id,
            'objednavky_stav_id' => rand(1, 4),
            'created' => date('Y-m-d H:i:s', mt_rand(strtotime('-6 months'), strtotime('-10 days'))),
            'datum_vyzvednuti' => date('Y-m-d H:i:s', mt_rand(strtotime('-5 months'), strtotime('+1 month'))),
            'objem' => rand(7, 50),
            'jmeno' => $user->jmeno
        ]);
    }
}