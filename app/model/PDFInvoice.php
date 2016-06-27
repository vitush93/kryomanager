<?php
/**
 * Created by PhpStorm.
 * User: vitush
 * Date: 27.06.2016
 * Time: 16:45
 */

namespace App\Model;


class PDFInvoice extends \TCPDF
{
    public function Header()
    {
        $this->setJPEGQuality(90);

    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'I', 8);
        $this->Cell(0, 10, 'Kryogenní pavilon MFF UK', 0, false, 'C');
    }

    public function CreateTextBox($textval, $x = 0, $y, $width = 0, $height = 10, $fontsize = 10, $fontstyle = '', $align = 'L')
    {
        $this->SetXY($x + 20, $y); // 20 = margin left
        $this->SetFont(PDF_FONT_NAME_MAIN, $fontstyle, $fontsize);
        $this->Cell($width, $height, $textval, 0, false, $align);
    }
}