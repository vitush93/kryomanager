<?php

namespace App\Presenters;

use App\Model\UserManager;
use Nette\Application\UI\Presenter;
use Nette\Security\IUserStorage;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Presenter
{
    protected function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            if ($this->user->storage->getLogoutReason() == IUserStorage::INACTIVITY) {
                $this->flashMessage('Byl jste automaticky odhlášen (20 min neaktivita).', 'info');
            }

            $this->redirect('Sign:default');
        }
    }
}
