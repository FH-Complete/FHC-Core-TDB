<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class TDB extends Auth_Controller
{

	private $_ci;

	private $_uid;

	private $uebermittlungsID;

	private $test;
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'index'=>'admin:rw',
				'export' => 'admin:rw',
				'csvExport' => 'admin:rw',
				'csvImport' => 'admin:rw'
			)
		);

		$this->_ci =& get_instance();
		$this->_setAuthUID();
		$this->setControllerId(); // sets the controller id

		$this->_ci->load->model('crm/Konto_model', 'KontoModel');
		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBExport_model', 'TDBExportModel');
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBBPKS_model', 'TDBBPKSModel');
		$this->_ci->load->library('AuthLib');
		$this->_ci->load->library('DocumentLib');
		$this->_ci->load->library('WidgetLib');
		$this->_ci->load->library('PhrasesLib');
		$this->_ci->load->helper('hlp_sancho_helper');

		$this->_ci->loadPhrases(
			array(
				'person',
				'global',
				'lehre',
				'filter',
				'ui'
			)
		);
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{
		$date = $this->_ci->input->get('exportDate');
		$csvExportDate = $this->_ci->input->get('csvExportDate');

		if (is_null($date))
		{
			$semester = $this->_ci->StudiensemesterModel->getLastOrAktSemester();
			$semester = $this->_ci->StudiensemesterModel->getPreviousFrom(getData($semester)[0]->studiensemester_kurzbz);
			$date = getData($semester)[0]->start;
		}

		if (is_null($csvExportDate))
		{
			$semester = $this->_ci->StudiensemesterModel->getLastOrAktSemester();
			$semester = $this->_ci->StudiensemesterModel->getPreviousFrom(getData($semester)[0]->studiensemester_kurzbz);
			$csvExportDate = getData($semester)[0]->start;
		}

		$data = ['date' => $date, 'csvExportDate' => $csvExportDate];

		$this->load->view('extensions/FHC-Core-TDB/bpkExport', $data);
	}

	public function export()
	{
		$exportDate = $this->_ci->input->get('exportDate');
		$testExport = $this->_ci->input->get('bpkExportTest');

		if ($testExport === 'false')
			$this->test = false;
		elseif ($testExport === 'true')
			$this->test = true;
		else
			$this->terminateWithJsonError("Falsche Parameterübergabe");

		$this->domDoc = new DOMDocument('1.0', 'UTF-8');

		$rootElement = $this->createRootElement();
		$this->createHeaderElement($rootElement, $testExport);
		$this->createBodyElement($rootElement, $exportDate);

		header('Content-Type: text/xml');
		header('Content-Disposition: attachment; filename="Export_' . $exportDate . '.xml"');
		$output = $this->domDoc->saveXML();
		echo $output;
	}

	public function csvImport()
	{
		if (is_uploaded_file($_FILES['csvFile']['tmp_name']))
		{
			$handle = fopen ($_FILES['csvFile']['tmp_name'],"r");

			while (($data = fgetcsv ($handle, 10000, ";")) !== FALSE)
			{
				//var_dump($data);
			}
		}
	}

	public function csvExport()
	{
		$exportDate = $this->_ci->input->get('csvExportDate');

		$result = $this->getForderfaelleData($exportDate);

		if (!hasData($result))
			$this->terminateWithJsonError('Keine Buchungen gefunden.');

		foreach (getData($result) as $key => $row)
		{
			if (is_null($row->person_id))
				continue;

			$check = $this->_ci->TDBBPKSModel->loadWhere(array('person_id' => $row->person_id));

			if (!hasData($check))
				$this->_ci->TDBBPKSModel->insert(array('person_id' => $row->person_id));
		}

		$filename = "BPK_XZVR-0074476426_01.csv";
		header('Content-type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename='.$filename);

		$file = fopen('php://output', 'w');

		$csvData = array('LAUFNTR', 'NACHNAME', 'VORNAME', 'GEBDATUM', 'NAME_VOR_ERSTER_EHE', 'GEBORT', 'GESCHLECHT', 'STAATSANGEHÖRIGKEIT',
						'ANSCHRIFTSSTAAT', 'GEMEINDENAME', 'PLZ', 'STRASSE', 'HAUSNR');

		fputcsv($file, $csvData, ';');

		foreach (getData($result) as $key => $row)
		{
			$csvRow = array($row->person_id, $row->nachname, $row->vorname, $row->gebdatum, '',
				$row->gebort, $row->geschlecht, $row->staatsangehoerigkeit, $row->anschriftsstaat,
				$row->gemeinde, $row->plz, '', '');

			fputcsv($file, $csvRow, ';');
		}
		fclose($file);
		exit();
	}

	private function getForderfaelleData($exportDate)
	{
		$this->_ci->KontoModel->addJoin('public.tbl_person', 'person_id');
		$this->_ci->KontoModel->addJoin('public.tbl_adresse', 'person_id');
		$this->_ci->KontoModel->addJoin('bis.tbl_nation s', 'staatsbuergerschaft = s.nation_code');
		$this->_ci->KontoModel->addJoin('bis.tbl_nation a', 'nation = a.nation_code');
		$this->_ci->KontoModel->addJoin('public.tbl_studiensemester ss', 'tbl_konto.studiensemester_kurzbz = ss.studiensemester_kurzbz');
		$this->_ci->KontoModel->addJoin('public.tbl_studienjahr sj', 'ss.studienjahr_kurzbz = sj.studienjahr_kurzbz');
		$this->_ci->KontoModel->addJoin('extension.tbl_tdb_bpks bpks', 'bpks.person_id = tbl_person.person_id', 'LEFT');
		$this->_ci->KontoModel->addSelect('SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 1 ) as startjahr,
										CONCAT(20, SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 2 )) as endjahr,
										matr_nr,
										tbl_konto.*,
										ABS(betrag) AS betrag,
										tbl_person.*,
										s.iso3166_1_a3 AS staatsangehoerigkeit,
										a.iso3166_1_a3 AS anschriftsstaat,
										tbl_adresse.*, bpks.*,
										tbl_person.person_id as person_id');
		/*
		 * OR tbl_konto.buchungstyp_kurzbz = 'ZuschussIO'
		 */
		return $this->_ci->KontoModel->loadWhere(
			"(tbl_konto.buchungstyp_kurzbz = 'Leistungsstipendium')
			AND tbl_adresse.zustelladresse = true
			AND tbl_konto.buchungsdatum >= " . $this->_ci->KontoModel->escape($exportDate). "
			AND 0 = (
				SELECT sum(betrag)
				FROM public.tbl_konto skonto
				WHERE skonto.buchungsnr = tbl_konto.buchungsnr_verweis
					OR skonto.buchungsnr_verweis = tbl_konto.buchungsnr_verweis
			)"
		);
	}

	private function createRootElement()
	{
		$root = $this->domDoc->createElement('UebermittlungFoerderfallLeistungsdaten');

		$xmlns = $this->domDoc->createAttribute('xmlns');
		$xmlnsVal = $this->domDoc->createTextNode('http://transparenzportal.gv.at/foerderfallLeistungsdaten');
		$xmlns->appendChild($xmlnsVal);

		$xsi = $this->domDoc->createAttribute('xmlns:xsi');
		$xsiVal = $this->domDoc->createTextNode('http://www.w3.org/2001/XMLSchema-instance');
		$xsi->appendChild($xsiVal);

		$root->appendChild($xmlns);
		$root->appendChild($xsi);

		$this->domDoc->appendChild($root);
		return $root;
	}

	public function createHeaderElement($rootElement, $testExport)
	{
		$header = $this->domDoc->createElement('Header');
		$rootElement->appendChild($header);

		$i = 0;
		do {
			$i++;
			$this->uebermittlungsID = 'UEB-FTHW-TDB-'. date('Y-m-d') . '-' . $i;
			$check = $this->_ci->TDBExportModel->loadWhere(array('uebermittlung_id' => $this->uebermittlungsID));
		} while(hasData($check));

		$headerValue = array(
			'OkzUeb' => 'XZVR-0074476426',
			'NameUeb' => 'Fachhochschule Technikum Wien',
			'UebermittlungsId' => $this->uebermittlungsID,
			'TsErstellung' => date('Y-m-d\TH:i:s'),
			'Test' => $testExport
		);
		$this->addToXml($headerValue, $header);
	}

	public function createBodyElement($rootElement, $exportDate)
	{
		$foerderfaelle = $this->getForderfaelle($exportDate);

		$aufruferReferenzVal = 1;

		foreach($foerderfaelle as $foerderfall)
		{
			//Der Förderfall
			$foerderfallLeistungsdatenElement = $this->createFoerderfallLeistungsdaten($aufruferReferenzVal);
			$rootElement->appendChild($foerderfallLeistungsdatenElement);
			$foerderfallElement = $this->domDoc->createElement('Foerderfall');
			$this->addToXml($foerderfall['Foerderfall'], $foerderfallElement);
			$foerderfallLeistungsdatenElement->appendChild($foerderfallElement);

			$aufruferReferenzVal++;

			//Die Auszahlung
			$foerderfallLeistungsdatenElement = $this->createFoerderfallLeistungsdaten($aufruferReferenzVal);
			$rootElement->appendChild($foerderfallLeistungsdatenElement);
			$leistungsDatenElement = $this->domDoc->createElement('Leistungsdaten');
			$this->addToXml($foerderfall['Leistungsdaten'], $leistungsDatenElement);
			$foerderfallLeistungsdatenElement->appendChild($leistungsDatenElement);

			$aufruferReferenzVal++;
		}


	}

	private function createFoerderfallLeistungsdaten($aufruferReferenzVal)
	{
		$leistungsDaten = $this->domDoc->createElement('FoerderfallLeistungsdaten');

		$aktionAttr = $this->domDoc->createAttribute('Aktion');
		$aktionVal = $this->domDoc->createTextNode('E');
		$aktionAttr->appendChild($aktionVal);

		$aufruferReferenzAttr = $this->domDoc->createAttribute('AufruferReferenz');
		$aktionVal = $this->domDoc->createTextNode($aufruferReferenzVal);
		$aufruferReferenzAttr->appendChild($aktionVal);

		$leistungsDaten->appendChild($aktionAttr);
		$leistungsDaten->appendChild($aufruferReferenzAttr);
		return $leistungsDaten;
	}

	private function getForderfaelle($exportDate)
	{
		$result = $this->getForderfaelleData($exportDate);

		if (!hasData($result))
		{
			$this->terminateWithJsonError('Keine Buchungen gefunden.');
		}

		$array = array();
		foreach (getData($result)  as $key => $leistungsstipendium)
		{
			if (!$this->test)
			{
				$check = $this->_ci->TDBExportModel->loadWhere(array('vorgangs_id' => $leistungsstipendium->buchungsnr));

				if (hasData($check))
					continue;
			}

			$array[$key]['Foerderfall'] = array(
				'VorgangsId' => $leistungsstipendium->buchungsnr,
				'FoerderfallId' => $leistungsstipendium->buchungsnr,
				'LeistungsangebotID' => '1007202', //1007202 in Prod 1047349 in Test
				'Foerdergegenstand' => 'F4186Q1006',
				'Status' => array(
					'Datum' => $leistungsstipendium->buchungsdatum,
					'Status' => 'gewaehrt',
					'Betrag' => $leistungsstipendium->betrag
				),
				'Foerdergeber' => array(
					'OkzLst' => 'XZVR-0074476426',
					'NameLst' => 'Fachhochschule Technikum Wien'
				),
				'Foerdernehmer' => array(
					'FoerdernehmerNatPers' => array(
						'vbPK_ZP_TD' => $leistungsstipendium->vbpk_zp_td,
						'vbPK_AS' => $leistungsstipendium->vbpk_as
					)
				),
				'Kontaktinfo' => array(
					'Kontakt' => 'Gerhard Brandstätter'
					/*'KontaktEmail' => '',
					'KontaktTel' => ''*/
				),
			);

			$array[$key]['Leistungsdaten'] = array(
				'FoerderfallId' => $leistungsstipendium->buchungsnr,
				'LeistungsdatenId' => $leistungsstipendium->buchungsnr,
				'Leistungsbezeichnung' => 'Leistungsstipendium',
				'Betrag' => $leistungsstipendium->betrag,
				'JahrVon' => $leistungsstipendium->startjahr,
				'JahrBis' => $leistungsstipendium->endjahr,
				'DatumAuszahlung' => $leistungsstipendium->buchungsdatum,
				'Foerdergeber' =>
					array(
						'OkzLst' => 'XZVR-0074476426',
						'NameLst' => 'Fachhochschule Technikum Wien'
					)
			);

			if (!$this->test)
				$this->addToExport($leistungsstipendium->buchungsnr);
		}
		return $array;
	}

	private function addToXml($values, $element)
	{
		foreach ($values as $key => $value)
		{
			if(is_array($value))
			{
				$keyElement = $this->domDoc->createElement($key);
				$element->appendChild($keyElement);
				$this->addToXml($value, $keyElement);
			}
			else
			{
				$subElt = $this->domDoc->createElement($key);
				$subNode = $element->appendChild($subElt);
				$textNode = $this->domDoc->createTextNode($value);
				$subNode->appendChild($textNode);
			}
		}
	}

	private function addToExport($vorgangs_id)
	{
		$this->_ci->TDBExportModel->insert(
			array(
				'uebermittlung_id' => $this->uebermittlungsID,
				'vorgangs_id' => $vorgangs_id
			)
		);
	}
	/**
	 * Retrieve the UID of the logged user and checks if it is valid
	 */
	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();

		if (!$this->_uid) show_error('User authentification failed');
	}
}
