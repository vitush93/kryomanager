<?php

namespace App\Forms;


use App\Model\UserManager;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Object;
use Nette\Security\Passwords;
use Nette\Security\User;

class AccFormFactory extends Object
{
    /** @var Context */
    private $db;

    /** @var bool|mixed|\Nette\Database\Table\IRow */
    private $user;

    /**
     * AccFormFactory constructor.
     * @param Context $context
     * @param UserManager $userManager
     * @param User $user
     */
    function __construct(Context $context, UserManager $userManager, User $user)
    {
        $this->db = $context;
        $this->user = $userManager->find($user->id);
    }

    /**
     * @return Form
     */
    function create()
    {
        $form = new Form();

        $form->addText('email', 'E-mail')
            ->setRequired(FORM_REQUIRED)
            ->addRule(Form::EMAIL)
            ->setDefaultValue($this->user->email);
        $form->addText('jmeno', 'Jméno')
            ->setRequired(FORM_REQUIRED)
            ->setDefaultValue($this->user->jmeno);
        $form->addPassword('heslo', 'Heslo')
            ->setOption('description', 'Vyplňte pokud chcete změnit heslo.')
            ->addCondition(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 6)
            ->setRequired(FORM_REQUIRED);
        $form->addPassword('heslo2', 'Heslo znovu')
            ->setOmitted()
            ->addConditionOn($form['heslo'], Form::FILLED)
            ->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['heslo']);
        $form->addSubmit('process', 'Uložit');

        $form->onSuccess[] = $this->formSucceeded;

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @param Form $form
     * @param $values
     */
    function formSucceeded(Form $form, $values)
    {
        if ($values->heslo) {
            $values->heslo = Passwords::hash($values->heslo);
        } else {
            unset($values->heslo);
        }

        try {
            $this->db->table(UserManager::TABLE_USERS)
                ->where('id', $this->user->id)
                ->update($values);
        } catch (UniqueConstraintViolationException $e) {
            $form->addError('Uživatel s tímto e-mailem již existuje.');
        }
    }

    /**
     * @return bool|mixed|\Nette\Database\Table\IRow
     */
    public function getUser()
    {
        return $this->user;
    }
}