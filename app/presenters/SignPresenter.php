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

        if ($this->user->isLoggedIn() && $this->action != 'out') {
            $this->redirect('Homepage:default');
        }
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
        $form->addCheckbox('remember', 'Zapamatovat')
            ->setDefaultValue(true);
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
            if ($values->remember) {
                $this->user->setExpiration('14 days');
            } else {
                $this->user->setExpiration('20 minutes');
            }

            $this->user->login($values->email, $values->password);

            $this->flashMessage('Byl jste úspěšně přihlášen', 'info');
            $this->redirect('Homepage:default');
        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

    function actionOut()
    {
        $this->user->logout();

        $this->redirect('default');
    }

}