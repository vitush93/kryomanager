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

    const ROLE_ADMIN = 'admin',
        ROLE_USER = 'user',
        ROLE_KRYO = 'kryo';


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

    function table()
    {
        return $this->database->table(self::TABLE_USERS);
    }

    function find($id)
    {
        return $this->database->table(self::TABLE_USERS)
            ->where('id', $id)
            ->fetch();
    }

    /**
     * @param int $id user's id.
     * @param Nette\Utils\ArrayHash $values edit form values.
     */
    function updateUser($id, $values)
    {
        if ($values->heslo) {
            $values->heslo = Passwords::hash($values->heslo);
        } else {
            unset($values->heslo);
        }

        $this->table()->where('id', $id)->update($values);
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
     * @param UserBuilder $userBuilder
     * @throws DuplicateEmailException
     */
    public function add(UserBuilder $userBuilder)
    {
        try {
            $data = $userBuilder->getData();

            $this->table()->insert($data);
        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            throw new DuplicateEmailException;
        }
    }

}

class UserBuilder extends Nette\Object
{
    const MIN_PASSWORD_LENGTH = 6;

    private $data;

    function __construct()
    {
        $this->data = new Nette\Utils\ArrayHash();
    }

    function getData()
    {
        return $this->data;
    }

    function setData(Nette\Utils\ArrayHash $data)
    {
        $this->data = $data;
    }

    function setEmail($email)
    {
        if (!Nette\Utils\Validators::isEmail($email)) {
            throw new Nette\InvalidArgumentException("$email is not a valid e-mail address.");
        }

        $this->data->email = $email;

        return $this;
    }

    function setName($name)
    {
        if (!$name) {
            throw new Nette\InvalidArgumentException('Valid user name not provided.');
        }

        $this->data->name = $name;

        return $this;
    }

    function setPassword($password)
    {
        if ($password && strlen($password) >= self::MIN_PASSWORD_LENGTH) {
            $this->data->password = Passwords::hash($password);
        } else {
            throw new Nette\InvalidArgumentException("Password must be at least " . self::MIN_PASSWORD_LENGTH . " characters.");
        }

        return $this;
    }

    function setRole($role)
    {
        if ($role && in_array($role, [UserManager::ROLE_ADMIN, UserManager::ROLE_USER, UserManager::ROLE_KRYO])) {
            $this->data->role = $role;
        } else {
            throw new Nette\InvalidArgumentException("Role $role is not valid.");
        }

        return $this;
    }

    function setInstitution($instituce)
    {
        if ($instituce instanceof Nette\Database\Table\ActiveRow) {
            $this->data->instituce_id = $instituce->id;
        } else if ($instituce) {
            $this->data->instituce_id = $instituce;
        } else {
            throw new Nette\InvalidArgumentException("Not a valid institution: $instituce.");
        }

        return $this;
    }

    function setGroup($skupina)
    {
        if ($skupina instanceof Nette\Database\Table\ActiveRow) {
            $this->data->skupiny_id = $skupina->id;
        } else if ($skupina) {
            $this->data->skupiny_id = $skupina;
        } else {
            throw new Nette\InvalidArgumentException("Not a valid group: $skupina.");
        }

        return $this;
    }

}


class DuplicateEmailException extends \Exception
{
}