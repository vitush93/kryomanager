<?php

namespace App\Model;


use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\InvalidArgumentException;
use Nette\Object;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class OrderManager extends Object
{
    const TABLE_ORDERS = 'objednavky';
    const TABLE_PRICES = PriceManager::TABLE_PRICES;
    const TABLE_PRODUCTS = 'produkty';

    const ORDER_STATUS_PENDING = 1,
        ORDER_STATUS_CANCELLED = 2,
        ORDER_STATUS_COMPLETED = 3,
        ORDER_STATUS_FINISHED = 4;

    const PRODUCT_HELIUM = 1,
        PRODUCT_NITROGEN = 2;

    /** @var Context */
    private $db;

    /** @var Settings */
    private $settings;

    /** @var UserManager */
    private $userManager;

    /** @var InstitutionManager */
    private $institutionManager;

    /**
     * OrderManager constructor.
     * @param Context $context
     * @param InstitutionManager $institutionManager
     * @param Settings $settings
     * @param UserManager $userManager
     */
    function __construct(Context $context,
                         InstitutionManager $institutionManager,
                         Settings $settings,
                         UserManager $userManager)
    {
        $this->db = $context;
        $this->userManager = $userManager;
        $this->settings = $settings;
        $this->institutionManager = $institutionManager;
    }

    /**
     * @param int $id order ID
     * @param int $status order status ID
     * @return int
     */
    function setStatus($id, $status)
    {
        return $this->order($id)->update([
            'objednavky_stav_id' => $status
        ]);
    }

    /**
     * @param int $id
     * @return bool|mixed|\Nette\Database\Table\IRow
     */
    function find($id)
    {
        return $this->order($id)->fetch();
    }

    /**
     * @param $user_id
     * @return Selection
     */
    function userOrders($user_id)
    {
        return $this->allOrders()->where('uzivatele_id', $user_id);
    }

    /**
     * @return array
     */
    public function productPairs()
    {
        return $this->db->table(self::TABLE_PRODUCTS)
            ->fetchPairs('id', 'nazev');
    }

    /**
     * @param $id
     * @return Selection
     */
    private function order($id)
    {
        return $this->db->table(self::TABLE_ORDERS)
            ->where('id', $id);
    }

    /**
     * @param int $id
     * @param float|int $returned_volume
     * @return int
     */
    function finishOrder($id, $returned_volume = 0)
    {
        $objem = $this->order($id)->fetch()->objem;
        if ($objem < $returned_volume) throw new InvalidArgumentException('Vrácený objem nemůže být větší než objem v objednávce.');

        return $this->order($id)
            ->where('objednavky_stav_id IN (?)', [
                self::ORDER_STATUS_COMPLETED,
                self::ORDER_STATUS_PENDING
            ])
            ->update([
                'objednavky_stav_id' => self::ORDER_STATUS_FINISHED,
                'objem_vraceno' => $returned_volume,
                'dokonceno' => new DateTime()
            ]);
    }

    /**
     * @param int $id
     * @param float|int $returned_volume
     * @return int
     */
    function finishCompletedOrder($id, $returned_volume = 0)
    {
        return $this->order($id)
            ->where('objednavky_stav_id', self::ORDER_STATUS_COMPLETED)
            ->update([
                'objednavky_stav_id' => self::ORDER_STATUS_FINISHED,
                'objem_vraceno' => $returned_volume,
                'dokonceno' => new DateTime()
            ]);
    }

    /**
     * @param int $id
     * @return int
     */
    function completeOrder($id)
    {
        return $this->order($id)
            ->update([
                'objednavky_stav_id' => self::ORDER_STATUS_COMPLETED,
                'vyrizeno' => new DateTime()
            ]);
    }

    /**
     * @param int $id
     * @return int
     */
    function completePendingOrder($id)
    {
        return $this->order($id)
            ->where('objednavky_stav_id', self::ORDER_STATUS_PENDING)
            ->update([
                'objednavky_stav_id' => self::ORDER_STATUS_COMPLETED,
                'vyrizeno' => new DateTime()
            ]);
    }

    /**
     * @param int $id
     * @return int
     */
    function cancelPendingOrder($id)
    {
        return $this->order($id)
            ->where('objednavky_stav_id', self::ORDER_STATUS_PENDING)
            ->update([
                'objednavky_stav_id' => self::ORDER_STATUS_CANCELLED,
                'stornovano' => new DateTime()
            ]);
    }

    /**
     * Check if order belongs to a user.
     *
     * @param int $user_id
     * @param int $order_id
     * @return bool
     */
    function hasOrder($user_id, $order_id)
    {
        $order = $this->order($order_id)
            ->where('uzivatele_id', $user_id)
            ->fetch();

        return $order !== false;
    }

    /**
     * @return Selection
     */
    function countOrders()
    {
        return $this->db->table(self::TABLE_ORDERS)
            ->select('COUNT(id) AS count');
    }

    /**
     * @return Selection
     */
    function getOrders()
    {
        return $this->db->table(self::TABLE_ORDERS)
            ->select('
            objednavky.id AS id,
            objednavky.created AS created, 
            objednavky.objem AS objem, 
            objednavky.objem_vraceno AS objem_vraceno, 
            objednavky.jmeno AS jmeno, 
            objednavky.dph AS dph,
            objednavky.objednavky_stav_id AS stav_id,
            ceny.cena AS cena,
            (cena * objem) AS cena_celkem,
            (cena * objem + cena * objem * objednavky.dph/100) AS cena_celkem_dph,
            produkty.nazev AS produkt,
            uzivatele.email AS email,
            skupiny.nazev AS skupina,
            instituce.nazev AS instituce,
            objednavky_stav.nazev AS stav');
    }

    /**
     * @return Selection
     */
    function getPendingOrders()
    {
        return $this->getOrders()
            ->where('objednavky_stav_id', self::ORDER_STATUS_PENDING);
    }

    /**
     * @param $user_id
     * @return Selection
     */
    function getPendingOrdersForUser($user_id)
    {
        return $this->getOrdersForUser($user_id)
            ->where('objednavky_stav_id', self::ORDER_STATUS_PENDING);
    }

    /**
     * @param $user_id
     * @return Selection
     */
    function getOrdersForUser($user_id)
    {
        return $this->getOrders()
            ->where('uzivatele_id', $user_id);
    }

    /**
     * @param int $user_id
     * @return array|\Nette\Database\Table\IRow[]
     */
    function getPricelistForUser($user_id)
    {
        $user = $this->userManager->find($user_id);
        $now = new DateTime();

        return $this->db->table(OrderManager::TABLE_PRICES)
            ->where('instituce_id', $user->instituce_id)
            ->where('platna_od <= ?', $now)
            ->where('platna_do >= ? OR platna_do IS NULL', $now)
            ->fetchAll();
    }

    /**
     * @return Selection
     */
    function allOrders()
    {
        return $this->db->table(self::TABLE_ORDERS);
    }

    /**
     * @param int $product_id
     * @param float $volume amount of kryoliquid
     * @param int $user_id
     * @param $datum_vyzvednuti
     * @param null $address
     * @param null $ico
     * @param null $dic
     * @param null $ucet
     */
    function add($product_id, $volume, $user_id, $datum_vyzvednuti, $address = null, $ico = null, $dic = null, $ucet = null, $pdf = null)
    {
        $objednavka = new ArrayHash();

        $objednavka->adresa = $address;
        $objednavka->ico = $ico;
        $objednavka->dic = $dic;
        $objednavka->ucet = $ucet;
        $objednavka->datum_vyzvednuti = $datum_vyzvednuti;
        $objednavka->pdf = $pdf;

        // objem, produkt, uzivatel
        $objednavka->objem = $volume;
        $objednavka->produkty_id = $product_id;
        $objednavka->uzivatele_id = $user_id;

        // jmeno, skupina, instituce
        $user = $this->userManager->find($user_id);
        $objednavka->jmeno = $user->jmeno;
        $objednavka->skupiny_id = $user->skupiny_id;
        $objednavka->instituce_id = $user->instituce_id;

        // get price id
        $now = new DateTime();
        $objednavka->ceny_id = $this->db->table(self::TABLE_PRICES)
            ->where('produkty_id', $product_id)
            ->where('instituce_id', $user->instituce_id)
            ->where('platna_od <= ?', $now)
            ->where('platna_do >= ? OR platna_do IS NULL', $now)
            ->fetch()->id;

        // get dph percentage
        $dph_config_key = $this->institutionManager
            ->findInstitution($user->instituce_id)
            ->dph;
        $dph = $this->settings->get($dph_config_key);
        $objednavka->dph = $dph;

        $this->db->table(self::TABLE_ORDERS)->insert($objednavka);
    }

    /**
     * TODO
     * @param $objednavka
     */
    function createInvoiceFromOrder($objednavka)
    {
        $pdf = new PDFInvoice(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Kryogenní pavilon');
        $pdf->SetTitle('Faktura');
        $pdf->SetSubject('Faktura');

        $pdf->AddPage();

        // create address box
        $pdf->CreateTextBox('Customer name Inc.', 0, 55, 80, 10, 10, 'B');
        $pdf->CreateTextBox('Mr. Tom Cat', 0, 60, 80, 10, 10);
        $pdf->CreateTextBox('Street address', 0, 65, 80, 10, 10);
        $pdf->CreateTextBox('Zip, city name', 0, 70, 80, 10, 10);

        // invoice title / number
        $pdf->CreateTextBox('Invoice #201012345', 0, 90, 120, 20, 16);

        // date, order ref
        $pdf->CreateTextBox('Date: ' . date('Y-m-d'), 0, 100, 0, 10, 10, '', 'R');
        $pdf->CreateTextBox('Order ref.: #6765765', 0, 105, 0, 10, 10, '', 'R');

        // list headers
        $pdf->CreateTextBox('Quantity', 0, 120, 20, 10, 10, 'B', 'C');
        $pdf->CreateTextBox('Product or service', 20, 120, 90, 10, 10, 'B');
        $pdf->CreateTextBox('Price', 110, 120, 30, 10, 10, 'B', 'R');
        $pdf->CreateTextBox('Amount', 140, 120, 30, 10, 10, 'B', 'R');

        $pdf->Line(20, 129, 195, 129);

        // some example data
        $orders[] = array('quant' => 5, 'descr' => '.com domain registration', 'price' => 9.95);
        $orders[] = array('quant' => 3, 'descr' => '.net domain name renewal', 'price' => 11.95);
        $orders[] = array('quant' => 1, 'descr' => 'SSL certificate 256-Byte encryption', 'price' => 99.95);
        $orders[] = array('quant' => 1, 'descr' => '25GB VPS Hosting, 200GB Bandwidth', 'price' => 19.95);

        $currY = 128;
        $total = 0;
        foreach ($orders as $row) {
            $pdf->CreateTextBox($row['quant'], 0, $currY, 20, 10, 10, '', 'C');
            $pdf->CreateTextBox($row['descr'], 20, $currY, 90, 10, 10, '');
            $pdf->CreateTextBox('$' . $row['price'], 110, $currY, 30, 10, 10, '', 'R');
            $amount = $row['quant'] * $row['price'];
            $pdf->CreateTextBox('$' . $amount, 140, $currY, 30, 10, 10, '', 'R');
            $currY = $currY + 5;
            $total = $total + $amount;
        }
        $pdf->Line(20, $currY + 4, 195, $currY + 4);

        // output the total row
        $pdf->CreateTextBox('Total', 20, $currY + 5, 135, 10, 10, 'B', 'R');
        $pdf->CreateTextBox('$' . number_format($total, 2, '.', ''), 140, $currY + 5, 30, 10, 10, 'B', 'R');

        // some payment instructions or information
        $pdf->setXY(20, $currY + 30);
        $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 10);
        $pdf->MultiCell(175, 10, '<em>Lorem ipsum dolor sit amet, consectetur adipiscing elit</em>. 
Vestibulum sagittis venenatis urna, in pellentesque ipsum pulvinar eu. In nec <a href="http://www.google.com/">nulla libero</a>, eu sagittis diam. Aenean egestas pharetra urna, et tristique metus egestas nec. Aliquam erat volutpat. Fusce pretium dapibus tellus.', 0, 'L', 0, 1, '', '', true, null, true);

        //Close and output PDF document
        $pdf->Output('test.pdf');
    }
}