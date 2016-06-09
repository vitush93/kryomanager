<?php


namespace App\Presenters;


use App\Forms\RegisterFormFactory;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\AuthenticationException;

class SignPresenter extends Presenter
{
    /** @var RegisterFormFactory @inject */
    public $registerFormFactory;

    protected function startup()
    {
        parent::startup();

        if ($this->user->isLoggedIn()) {
            $this->redirect('Homepage:default');
        }
    }

    protected function createComponentRegisterForm()
    {
        return $this->registerFormFactory->create(function () {
            $this->flashMessage('Registrace úspěšná. Nyní se můžete přihlásit.', 'success');
            $this->redirect('default');
        });
    }

    /**
     * @return Form
     */
    protected function createComponentLoginForm()
    {
        $form = new Form();

        $form->addText('email', 'E-mail')
            ->addRule(Form::EMAIL)
            ->setRequired();
        $form->addPassword('password', 'Heslo')
            ->setRequired();
        $form->addSubmit('process', 'Přihlásit');

        $form->onSuccess[] = $this->loginFormSucceeded;

        return BootstrapForm::makeBootstrap($form);
    }

    /**
     * @param Form $form
     * @param $values
     */
    function loginFormSucceeded(Form $form, $values)
    {
        try {
            $this->user->setExpiration('20 minutes');
            $this->user->login($values->email, $values->password);

            $this->flashMessage('Byl jste úspěšně přihlášen', 'info');
            $this->redirect('Homepage:default');
        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

}