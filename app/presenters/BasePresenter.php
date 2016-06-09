<?php

namespace App\Presenters;

use Nette;
use App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    protected function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            if ($this->user->storage->getLogoutReason() == Nette\Security\IUserStorage::INACTIVITY) {
                $this->flashMessage('Byl jste automaticky odhlášen (20 min neaktivita).', 'info');
            }

            $this->redirect('Sign:default');
        }
    }
}
