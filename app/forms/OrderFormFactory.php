<?php


namespace App\Forms;


use App\Model\OrderManager;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\Object;
use Nette\Security\User;

class OrderFormFactory extends Object
{

    /** @var Context */
    private $db;

    /** @var OrderManager */
    private $orderManager;

    /** @var User */
    private $user;

    /**
     * OrderFormFactory constructor.
     * @param Context $context
     * @param OrderManager $orderManager
     * @param User $user
     */
    function __construct(Context $context, OrderManager $orderManager, User $user)
    {
        $this->db = $context;
        $this->orderManager = $orderManager;
        $this->user = $user;
    }

    /**
     * @return array
     */
    private function products()
    {
        return $this->db->table('produkty')
            ->fetchPairs('id', 'nazev');
    }

    /**
     * @param callable|null $onSuccess
     * @return Form
     */
    function create(callable $onSuccess = null)
    {
        $form = new Form();

        $form->addSelect('produkty_id', 'Kryokapalina', $this->products())
            ->setRequired(FORM_REQUIRED)
            ->setPrompt('-vyberte-');
        $form->addText('objem', 'Objem')
            ->setOption('description', 'Zadejte objem v litrech.')
            ->addRule(Form::FLOAT, 'Objem musí být číslo.')
            ->setRequired(FORM_REQUIRED);
        $form->addSubmit('process', 'Odeslat');

        $form->onSuccess[] = function (Form $form, $values) use ($onSuccess) {
            $this->orderManager->add(
                $values->produkty_id,
                $values->objem,
                $this->user->id);

            if ($onSuccess) {
                $onSuccess();
            }
        };

        return BootstrapForm::makeBootstrap($form);
    }
}