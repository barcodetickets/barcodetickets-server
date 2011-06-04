<?php
require_once 'tcpdf/config/lang/eng.php';
require_once 'tcpdf/tcpdf.php';
class Panel_Model_PdfGenerator
{
	/**
	 * @var TCPDF
	 */
	private $_document;
	private $_preferences = array(
		'PrintScaling' => 'None',
		'Duplex' => 'Simplex');
	private $_labels = array();
	private $_barcodes = array();
	private $_labelsAreHtml = false;
	private $_font = 'DejaVuSansMono';
	public function __construct (array $data)
	{
		$this->_document = new TCPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
		$this->_document->SetFont($this->_font, '', 12);
		$this->_setMetadata();
		$this->_setBoundaries();
		// process $data into labels and barcodes
		foreach ($data as $d) {
			$this->_labels[] = $d[1];
			$this->_barcodes[] = $d[0];
			// if any single label has a '<' indicating a tag, switch HTML on
			if (! $this->_labelsAreHtml) {
				if (strpos($d[1], '<') !== false) {
					$this->_labelsAreHtml = true;
				}
			}
		}
		// decide if we need multiple pages
		if (count($data) <= 20) {
			$this->_document->AddPage();
			$this->_drawGrid();
			$this->_drawTextLabels($this->_labels);
			$this->_drawBarcodes($this->_barcodes);
		} else {
			for ($i = 0; $i < count($data); $i += 20) {
				$this->_document->AddPage();
				$this->_drawGrid();
				$this->_drawTextLabels(
					array_slice($this->_labels, $i, 20, true));
				$this->_drawBarcodes(
					array_slice($this->_barcodes, $i, 20, true));
			}
		}
	}
	private function _setBoundaries ()
	{
		$this->_document->SetPrintHeader(false);
		$this->_document->SetPrintFooter(false);
		$this->_document->SetMargins(13, 4.1, 13);
		$this->_document->SetAutoPageBreak(true, 4.1);
	}
	private function _setMetadata ()
	{
		$d = $this->_document;
		$d->SetCreator('Barcode Ticket System ' . BTS_VERSION);
		$d->SetTitle('Barcode Sheet');
		$d->SetDisplayMode('fullpage');
		$d->setViewerPreferences($this->_preferences);
	}
	private function _drawGrid ()
	{
		$d = $this->_document;
		for ($i = 0; $i < 5; $i ++) {
			// $d->Rect($i * 50.8 + 13, 4.1, 50.8, 101.6, '', 'S');
			// 109.7 = 4.1 + 101.6 + 4
			// $d->Rect($i * 50.8 + 13, 109.7, 50.8, 101.6, '', 'S');
			// divide each label in two
			$lineStyle = array(
				'color' => array(
					150,
					150,
					150));
			$d->Rect($i * 50.8 + 13, 4.1, 50.8, 50.8, '',
				array(
					'all' => $lineStyle));
			$d->Rect($i * 50.8 + 13, 54.9, 50.8, 50.8, '',
				array(
					'all' => $lineStyle));
			$d->Rect($i * 50.8 + 13, 109.7, 50.8, 50.8, '',
				array(
					'all' => $lineStyle));
			$d->Rect($i * 50.8 + 13, 160.5, 50.8, 50.8, '',
				array(
					'all' => $lineStyle));
		}
	}
	private function _drawTextLabels (array $_array)
	{
		$d = $this->_document;
		$counter = 0;
		foreach ($_array as $s) {
			switch ((int) ($counter / 5)) {
				case 0: // first row
					$d->SetY(44.9); // 54.9 - 4.9
					break;
				case 1:
					$d->SetY(95.7);
					break;
				case 2:
					$d->SetY(150.5);
					break;
				case 3:
					$d->SetY(201.3);
					break;
			}
			$col = $counter % 5;
			$d->SetX(13 + $col * 50.8);
			if ($this->_labelsAreHtml)
				$d->writeHtmlCell(50.8, 10, $d->GetX(), $d->GetY(), $s, 0, 0,
					false, true, 'C');
			else
				$d->Cell(50.8, 10, $s, 0, 0, 'C');
			$counter ++;
		}
	}
	private function _drawBarcodes (array $_array)
	{
		$d = $this->_document;
		$counter = 0;
		foreach ($_array as $s) {
			$x = 0;
			$y = 0;
			switch ((int) ($counter / 5)) {
				case 0: // first row
					$y = 9.1; // 4.1 + 5
					break;
				case 1:
					$y = 59.9;
					break;
				case 2:
					$y = 114.7;
					break;
				case 3:
					$y = 165.5;
					break;
			}
			$col = $counter % 5;
			$x = 20.5 + $col * 50.8;
			// $d->Cell(50.8, 10, $s, 0, 0, 'C');
			$d->write2DBarcode($s, 'QRCODE', $x, $y, 35, 35,
				array(), 'M');
			$counter ++;
		}
	}
	public function render ()
	{
		$this->_document->Output('pdf.pdf');
	}
}