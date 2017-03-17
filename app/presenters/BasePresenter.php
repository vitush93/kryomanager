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

    /**
     * @param $referer
     * @return string
     */
    protected function appendFlashMessage($referer)
    {
        $referer .= (parse_url($referer, PHP_URL_QUERY) ? '&' : '?') . '_fid=';
        $referer .= $this->getParameter(self::FLASH_KEY);

        return $referer;
    }
}
