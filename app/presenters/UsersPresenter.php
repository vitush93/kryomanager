<?php

namespace App\Presenters;


use App\Model\InstitutionManager;
use App\Model\OrderManager;
use App\Model\UserBuilder;
use App\Model\UserManager;
use Grido\Components\Filters\Filter;
use Grido\Grid;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;

class UsersPresenter extends BasePresenter
{
    /** @var UserManager @inject */
    public $userManager;

    /** @var InstitutionManager @inject */
    public $institutionManager;

    /** @var OrderManager @inject */
    public $orderManager;

    /**
     * @throws \Nette\Application\BadRequestException
     */
    protected function startup()
    {
        parent::startup();

        if (!$this->user->isInRole(UserManager::ROLE_ADMIN)) {
            $this->error();
        }
    }

    /**
     * List of users.
     */
    function actionDefault()
    {
        $this['newUserForm']->removeComponent($this['newUserForm']['cancel']);
        $this['newUserForm']['heslo']->setRequired(FORM_REQUIRED);

        $this['newUserForm']->onSuccess[] = $this->newUserFormSucceeded;

    }

    /**
     * @param int $id
     */
    function actionEdit($id)
    {
        $user = $this->userManager->find($id);
        if (!$user) {
            $this->error();
        }

        $user = $user->toArray();
        unset($user['heslo']);

        $this['userForm']->setDefaults($user);

        $this['userForm']->onSuccess[] = $this->userFormSucceeded;
    }

    /**
     * @param int $id user's id.
     */
    function renderDetail($id)
    {
        $this->template->u = $this->userManager->find($id);
        $this->template->objednavky = $this->orderManager->userOrders($id)->order('created', 'DESC');
    }

    /**
     * @param Form $form
     * @param $values
     */
    function userFormSucceeded(Form $form, $values)
    {
        $this->userManager->updateUser($this->getParameter('id'), $values);

        $this->flashMessage('Změny byly uloženy.', 'info');
        $this->redirect('default');
    }

    /**
     * @param Form $form
     * @param $values
     */
    function newUserFormSucceeded(Form $form, $values)
    {
        $values->heslo = Passwords::hash($values->heslo);

        $builder = new UserBuilder();
        $builder->setData($values);

        $this->userManager->add($builder);

        $this->flashMessage('Nový uživatel byl vytvořen.', 'success');
        $this->redirect('this');
    }

    protected function createComponentNewUserForm()
    {
        $form = $this->createComponentUserForm();

        $form['heslo']->setOption('description', '');

        return $form;
    }

    /**
     * @return Form
     */
    protected function createComponentUserForm()
    {
        $form = new Form();

        $form->addText('email', 'E-mail')
            ->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu.')
            ->setRequired(FORM_REQUIRED);
        $form->addText('jmeno', 'Jméno')
            ->setRequired(FORM_REQUIRED);
        $form->addText('heslo', 'Nové heslo')
            ->setOption('description', 'Vyplňte pokud chcete změnit.')
            ->addCondition(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 6)
            ->setRequired(FORM_REQUIRED);
        $form->addSelect('role', 'Role', [
            'admin' => 'Admin',
            'user' => 'Uživatel',
            'kryo' => 'Kryo'
        ])->setPrompt('-vyberte-')->setRequired(FORM_REQUIRED);
        $form->addSelect('instituce_id', 'Instituce', $this->institutionManager->institutionPairs())
            ->setPrompt('-vyberte-')
            ->setRequired(FORM_REQUIRED);
        $form->addSelect('skupiny_id', 'Skupina', $this->institutionManager->groupPairs())
            ->setPrompt('-vyberte-')
            ->setRequired(FORM_REQUIRED);

        $form->addSubmit('process', 'Uložit');
        $form->addSubmit('cancel', 'Zrušit')
            ->setValidationScope(false)
            ->onClick[] = function () {
            $this->redirect('default');
        };

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @return Grid
     * @throws \Grido\Exception
     */
    protected function createComponentUsersGrid()
    {
        $grid = new Grid();

        $grid->setModel($this->userManager->table());
        $grid->getTranslator()->setLang('cs');
        $grid->setDefaultSort(['created' => 'DESC']);

        $grid->addColumnNumber('id', '#')->setSortable();
        $grid->addColumnText('instituce.nazev', 'Instituce')->setSortable();
        $grid->addColumnText('skupiny.nazev', 'Skupina')->setSortable();
        $grid->addColumnText('jmeno', 'Jméno')->setSortable();
        $grid->addColumnText('email', 'E-mail')->setSortable();
        $grid->addColumnText('role', 'Role')->setSortable();


        $grid->setFilterRenderType(Filter::RENDER_INNER);
        $grid->addFilterNumber('id', '');

        $institucePairs = $this->institutionManager->institutionPairs();
        $instituceConditions = [];
        foreach ($institucePairs as $id => $nazev) {
            $instituceConditions[$id] = ['instituce.id', '= ?', $id];
        }
        $institucePairs[] = ['' => 'vše'];
        $grid->addFilterSelect('instituce.nazev', '', $institucePairs)
            ->setDefaultValue('')
            ->setCondition($instituceConditions);

        $skupinyPairs = $this->institutionManager->groupPairs();
        $skupinyConditions = [];
        foreach ($skupinyPairs as $id => $nazev) {
            $skupinyConditions[$id] = ['skupiny.id', '= ?', $id];
        }
        $skupinyPairs[] = ['' => 'vše'];
        $grid->addFilterSelect('skupiny.nazev', '', $skupinyPairs)
            ->setDefaultValue('')
            ->setCondition($skupinyConditions);

        $grid->addFilterText('jmeno', '');
        $grid->addFilterText('email', '');
        $grid->addFilterSelect('role', '', [
            '' => '',
            'admin' => 'Admin',
            'user' => 'Uživatel'
        ]);

        $grid->addActionHref('detail', 'Detail')
            ->setIcon('search');
        $grid->addActionHref('edit', 'Upravit')
            ->setIcon('pencil');

        return $grid;
    }
}