<?php

namespace App\Forms;


use App\Model\InstitutionManager;
use App\Model\UserManager;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Object;
use Nette\Security\Passwords;

class RegisterFormFactory extends Object
{
    /** @var Context */
    private $db;

    /** @var InstitutionManager */
    private $institutionManager;

    /**
     * RegisterFormFactory constructor.
     * @param Context $context
     * @param InstitutionManager $institutionManager
     */
    function __construct(Context $context, InstitutionManager $institutionManager)
    {
        $this->db = $context;
        $this->institutionManager = $institutionManager;
    }

    private function getInstitutionsPairs()
    {
        $arr = [];
        foreach ($this->db->table(InstitutionManager::INSTITUTION_TABLE_NAME) as $instituce) {
            foreach ($this->db->table(InstitutionManager::GROUP_TABLE_NAME)
                         ->where('instituce_id', $instituce->id) as $skupina) {
                $key = "{$instituce->id}_{$skupina->id}";
                $arr[$key] = "{$instituce->nazev} - {$skupina->nazev}";
            }
        }

        return $arr;
    }

    function create(callable $onSuccess = null)
    {
        $form = new Form();

        $form->addText('email', 'E-mail')
            ->addRule(Form::EMAIL, 'Zadejte e-mail ve správném formátu.')
            ->setRequired(BootstrapForm::REQUIRED_MSG);
        $form->addText('jmeno', 'Jméno')
            ->setRequired(BootstrapForm::REQUIRED_MSG);
        $form->addPassword('heslo', 'Heslo')
            ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 6)
            ->setRequired(BootstrapForm::REQUIRED_MSG);
        $form->addPassword('heslo2', 'Heslo znovu')
            ->addRule(Form::EQUAL, 'Hesla se neshodují.', $form['heslo'])
            ->setRequired(BootstrapForm::REQUIRED_MSG)
            ->setOmitted();
        $form->addSelect('skupina_instituce', 'Instituce a skupina', $this->getInstitutionsPairs());
        $form->addSubmit('process', 'Registrovat');

        $form->onSuccess[] = function (Form $form, $values) use ($onSuccess) {
            $this->formSucceeded($form, $values);

            if (!$form->hasErrors()) $onSuccess();
        };

        return BootstrapForm::makeBootstrap($form);
    }

    function formSucceeded(Form $form, $values)
    {
        $parts = explode('_', $values->skupina_instituce);
        unset($values->skupina_instituce);

        try {
            $instituce = $this->institutionManager->findInstitution($parts[0]);
            $skupina = $this->institutionManager->findGroup($parts[1]);

            if ($skupina && $instituce) {
                $values->instituce_id = $instituce->id;
                $values->skupiny_id = $skupina->id;

                $values->heslo = Passwords::hash($values->heslo);

                $this->db->table(UserManager::TABLE_NAME)->insert($values);
            }
        } catch (UniqueConstraintViolationException $e) {
            $form->addError('Uživatel s tímto e-mailem již existuje.');
        }
    }
}