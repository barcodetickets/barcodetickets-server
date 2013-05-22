<?php
require_once 'tcpdf/config/lang/eng.php';
require_once 'tcpdf/tcpdf.php';

class Panel_Model_PdfGenerator
{

	/**
	 *
	 * @var TCPDF
	 */
	private $_document;

	/**
	 *
	 * @var Zend_Config
	 */
	private $_config;

	private $_preferences = array(
			'PrintScaling' => 'None',
			'Duplex' => 'Simplex'
	);

	private $_labels = array();

	private $_barcodes = array();

	private $_font = 'DejaVuSansMono';

	private $_count = 0;

	public function __construct (array $data = array())
	{
		$this->_initConfig();
		$this->_document = new TCPDF('L', 'mm', 'Letter', true, 'UTF-8', false);
		$this->_document->SetFont($this->_font, '', 12);
		$this->_setMetadata();
		$this->_setBoundaries();
		$this->_setData($data);
	}

	protected function _initConfig ()
	{
		$this->_config = new Zend_Config_Ini(
				APPLICATION_PATH . '/configs/bts.ini.dist', 'pdf', true);
		if (file_exists(APPLICATION_PATH . '/configs/bts.ini')) {
			$this->_config->merge(
					new Zend_Config_Ini(APPLICATION_PATH . '/configs/bts.ini', 
							'pdf'));
		}
	}

	private function _setData (array $data)
	{
		if (count(next($data)) != 2)
			return false;
			// process $data into labels and barcodes
		foreach ($data as $d) {
			$this->_labels[] = $d[1];
			$this->_barcodes[] = $d[0];
		}
		$this->_count = count($data);
		return true;
	}

	public function setData (array $data)
	{
		if ($this->_count > 0)
			throw new Bts_Exception('Cannot set data when there is already data', 
					Bts_Exception::BARCODES_DATA_BAD);
		$result = $this->_setData($data);
		if (! $result)
			throw new Bts_Exception('Invalid data format', 
					Bts_Exception::BARCODES_DATA_BAD);
		return $this;
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
			// divide each label in two
			$lineStyle = array(
					'color' => array(
							150,
							150,
							150
					)
			);
			$d->Rect($i * 50.8 + 13, 4.1, 50.8, 50.8, '', 
					array(
							'all' => $lineStyle
					));
			$d->Rect($i * 50.8 + 13, 54.9, 50.8, 50.8, '', 
					array(
							'all' => $lineStyle
					));
			$d->Rect($i * 50.8 + 13, 109.7, 50.8, 50.8, '', 
					array(
							'all' => $lineStyle
					));
			$d->Rect($i * 50.8 + 13, 160.5, 50.8, 50.8, '', 
					array(
							'all' => $lineStyle
					));
		}
	}

	private function _drawTextLabels (array $_array)
	{
		$d = $this->_document;
		$counter = 0;
		foreach ($_array as $s) {
			switch ((int) ($counter / 5)) {
				case 0: // first row
					$d->SetY(43.6);
					break;
				case 1:
					$d->SetY(94.4);
					break;
				case 2:
					$d->SetY(149.2);
					break;
				case 3:
					$d->SetY(201.0);
					break;
			}
			$col = $counter % 5;
			$d->SetX(13 + $col * 50.8);
			if ($this->_config->html)
				$d->writeHtmlCell(50.8, 8, $d->GetX(), $d->GetY() + 1.3, $s, 0, 
						0, false, true, 'C');
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
			$d->write2DBarcode($s, 'QRCODE', $x, $y, 35, 35, array(), 'M');
			$counter ++;
		}
	}

	public function render ()
	{
		if ($this->_count == 0) {
			$this->_document->AddPage();
			$this->_config->html = false;
			$this->_drawTextLabels(array(
					'No barcodes exist.'
			));
		} elseif ($this->_count <= 20) { // decide if we need multiple pages
			$this->_document->AddPage();
			if ($this->_config->grid)
				$this->_drawGrid();
			$this->_drawTextLabels($this->_labels);
			$this->_drawBarcodes($this->_barcodes);
		} else {
			for ($i = 0; $i < $this->_count; $i += 20) {
				$this->_document->AddPage();
				if ($this->_config->grid)
					$this->_drawGrid();
				$this->_drawTextLabels(
						array_slice($this->_labels, $i, 20, true));
				$this->_drawBarcodes(
						array_slice($this->_barcodes, $i, 20, true));
			}
		}
		$this->_document->Output('pdf.pdf');
	}

	public function getLabelsAreHtml ()
	{
		return $this->_config->html;
	}
}