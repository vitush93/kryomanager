<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{

    const
        TABLE_USERS = 'uzivatele',
        COLUMN_ID = 'id',
        COLUMN_NAME = 'jmeno',
        COLUMN_PASSWORD_HASH = 'heslo',
        COLUMN_EMAIL = 'email',
        COLUMN_ROLE = 'role';


    /** @var Nette\Database\Context */
    private $database;

    /** @var InstitutionManager */
    private $institutionManager;

    /**
     * UserManager constructor.
     * @param Nette\Database\Context $database
     * @param InstitutionManager $institutionManager
     */
    public function __construct(Nette\Database\Context $database, InstitutionManager $institutionManager)
    {
        $this->database = $database;
        $this->institutionManager = $institutionManager;
    }

    function find($id)
    {
        return $this->database->table(self::TABLE_USERS)
            ->where('id', $id)
            ->fetch();
    }


    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        list($username, $password) = $credentials;

        $row = $this->database->table(self::TABLE_USERS)->where(self::COLUMN_EMAIL, $username)->fetch();

        if (!$row) {
            throw new Nette\Security\AuthenticationException('Uživatel s tímto e-mailem neexistuje.', self::IDENTITY_NOT_FOUND);

        } elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
            throw new Nette\Security\AuthenticationException('Špatné heslo.', self::INVALID_CREDENTIAL);

        } elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
            $row->update([
                self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
            ]);
        }

        $arr = $row->toArray();
        $skupina = $this->institutionManager->findGroup($arr['skupiny_id'])->toArray();
        $instituce = $this->institutionManager->findInstitution($arr['instituce_id'])->toArray();

        $arr['skupina'] = $skupina;
        $arr['instituce'] = $instituce;

        unset($arr[self::COLUMN_PASSWORD_HASH]);
        return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
    }


    /**
     * Adds new user.
     * @param  string
     * @param  string
     * @param  string
     * @return void
     * @throws DuplicateEmailException
     */
    public function add($email, $password, $name)
    {
        try {
            $this->database->table(self::TABLE_USERS)->insert([
                self::COLUMN_NAME => $name,
                self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
                self::COLUMN_EMAIL => $email,
            ]);
        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            throw new DuplicateEmailException;
        }
    }

}


class DuplicateEmailException extends \Exception
{
}