<?php


namespace App\Forms;


use App\Model\FileUploadHandler;
use App\Model\OrderManager;
use App\Model\UserManager;
use Grido\Components\Columns\Date;
use Libs\BootstrapForm;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\InvalidArgumentException;
use Nette\Neon\Exception;
use Nette\Object;
use Nette\Security\User;
use Nette\Utils\DateTime;

class OrderFormFactory extends Object
{

    /** @var Context */
    private $db;

    /** @var OrderManager */
    private $orderManager;

    /** @var bool|mixed|\Nette\Database\Table\IRow */
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
        $this->user = $context->table(UserManager::TABLE_USERS)
            ->where('id', $user->id)
            ->fetch();
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
        $form->addText('datum_vyzvednuti', 'Datum')
            ->setDefaultValue('zítra')
            ->setRequired(FORM_REQUIRED)
            ->setOption('description', 'Očekávané datum vyzvednutí.');
        $form->addUpload('pdf', 'Soubor s objednávkou')
            ->setOption('description', 'Volitelné pole: soubor s vaší objednávkou');

        if ($this->user->instituce->id == 1) { // check if user is external
            $form->addTextArea('adresa', 'Adresa', 10, 4);
            $form->addText('ico', 'IČO');
            $form->addText('dic', 'DIČ');
            $form->addText('ucet', 'Číslo účtu');
        }

        $form->addSubmit('process', 'Odeslat');

        $form->onSuccess[] = function (Form $form, $values) use ($onSuccess) {

            if ($values->pdf->isOk()) {
                $values->pdf = FileUploadHandler::upload($values->pdf);
            }

            try {
                if ($values->datum_vyzvednuti == 'zítra') {
                    $datum_vyzvednuti = new DateTime('tomorrow');
                } else {
                    $dnes = new DateTime();
                    $datum_vyzvednuti = new DateTime($values->datum_vyzvednuti);

                    if ($datum_vyzvednuti < $dnes) {
                        throw new InvalidArgumentException;
                    }
                }
            } catch (\Exception $e) {
                $form->addError('Zadejte prosím platné budoucí datum.');

                return;
            }

            $this->orderManager->add(
                $values->produkty_id,
                $values->objem,
                $this->user->id,
                $datum_vyzvednuti,
                isset($values->adresa) ? $values->adresa : null,
                isset($values->ico) ? $values->ico : null,
                isset($values->dic) ? $values->dic : null,
                isset($values->ucet) ? $values->ucet : null,
                isset($values->pdf) ? $values->pdf : null
            );

            if ($onSuccess) {
                $onSuccess();
            }
        };

        $form = BootstrapForm::makeBootstrap($form);

        $form['datum_vyzvednuti']->getControlPrototype()->class = 'form-control datepicker';

        return $form;
    }
}