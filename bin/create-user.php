<?php

if (!isset($_SERVER['argv'][5])) {
    echo '
Add new user to database.

Usage: create-user.php <email> <password> <name> <instituce> <skupina>
';
    exit(1);
}

list(, $name, $password) = $_SERVER['argv'];

$container = require __DIR__ . '/../app/bootstrap.php';

/** @var \Nette\Database\Context $db */
$db = $container->getByType('Nette\Database\Context');

try {
    $u = $_SERVER['argv'];

    $db->table('uzivatele')->insert([
        'email' => $u[1],
        'heslo' => \Nette\Security\Passwords::hash($u[2]),
        'jmeno' => $u[3],
        'instituce_id' => $u[4],
        'skupiny_id' => $u[5]
    ]);

    echo "User {$u[3]} was added.\n";

} catch (App\Model\DuplicateEmailException $e) {
    echo "Error: duplicate name.\n";
    exit(1);
}
