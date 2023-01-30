<?php

class TDBExportLib
{
	private $_ci;
	private $domDoc;
	private $uebermittlungsID;
	public $test;

	public function __construct()
	{
		$this->_ci =& get_instance();
		$this->_ci->load->config('extensions/FHC-Core-TDB/tdb');

		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBExport_model', 'TDBExportModel');
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBBPKS_model', 'TDBBPKSModel');

		$this->_ci->load->library('extensions/FHC-Core-TDB/TDBManagementLib');

	}

	public function createRootElement()
	{
		$this->domDoc = new DOMDocument('1.0', 'UTF-8');

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
		if ($testExport === 'false')
			$this->test = false;
		elseif ($testExport === 'true')
			$this->test = true;
		else
			return error("Falsche Parameterübergabe");

		$header = $this->domDoc->createElement('Header');
		$rootElement->appendChild($header);

		$i = 0;
		do {
			$i++;
			$this->uebermittlungsID = $this->_ci->config->item('uebermittlungs_id') . '-' . date('Y-m-d') . '-' . $i;
			$check = $this->_ci->TDBExportModel->loadWhere(array('uebermittlung_id' => $this->uebermittlungsID));
		} while(hasData($check));

		$headerValue = array(
			'OkzUeb' => $this->_ci->config->item('vkz'),
			'NameUeb' => $this->_ci->config->item('name_lst'),
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

		return $this->domDoc;
	}

	public function createXMLExport($exportDate)
	{
		header('Content-Type: text/xml');
		header('Content-Disposition: attachment; filename="Export_' . $exportDate . '.xml"');
		echo $this->domDoc->saveXML();
	}

	public function createCSVExport($exportDate)
	{
		$result = $this->_ci->tdbmanagementlib->getForderfaelleData($exportDate);

		if (!hasData($result))
			show_error('Keine Buchungen gefunden');

		foreach (getData($result) as $key => $row)
		{
			$check = $this->_ci->TDBBPKSModel->loadWhere(array('person_id' => $row->person_id));

			if (!hasData($check))
				$this->_ci->TDBBPKSModel->insert(array('person_id' => $row->person_id));
		}

		//Filenamenkonvention: BPK_<Verwaltungskennzeichen_Org>_<laufnr>.csv
		$filename = "BPK_" .  $this->_ci->config->item('vkz') . "_XX.csv";
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

		return $file;
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
		$result = $this->_ci->tdbmanagementlib->getForderfaelleData($exportDate);

		if (!hasData($result))
			show_error('Keine Buchungen gefunden.');

		$array = array();
		foreach (getData($result)  as $key => $leistungsstipendium)
		{
			if (!$this->test)
			{
				$check = $this->_ci->TDBExportModel->loadWhere(array('vorgangs_id' => $leistungsstipendium->buchungsnr));

				if (hasData($check))
					continue;
			}

			if (is_null($leistungsstipendium->vbpk_zp_td) || is_null($leistungsstipendium->vbpk_as))
				continue;

			$array[$key]['Foerderfall'] = array(
				'VorgangsId' => $leistungsstipendium->buchungsnr,
				'FoerderfallId' => $leistungsstipendium->buchungsnr,
				'leistungsangebot_id' => $this->_ci->config->item('leistungsangebot_id'),
				'Foerdergegenstand' => $this->_ci->config->item('foerdergegenstand'),
				'Status' => array(
					'Datum' => $leistungsstipendium->buchungsdatum,
					'Status' => $this->_ci->config->item('foerderfall_status'),
					'Betrag' => $leistungsstipendium->betrag
				),
				'Foerdergeber' => array(
					'OkzLst' => $this->_ci->config->item('vkz'),
					'NameLst' => $this->_ci->config->item('name_lst')
				),
				'Foerdernehmer' => array(
					'FoerdernehmerNatPers' => array(
						'vbPK_ZP_TD' => $leistungsstipendium->vbpk_zp_td,
						'vbPK_AS' => $leistungsstipendium->vbpk_as
					)
				),
				'Kontaktinfo' => array(
					'Kontakt' => $this->_ci->config->item('kontakt')/*,
					'KontaktEmail' => $this->_ci->config->item('kontakt_email'),
					'KontaktTel' => $this->_ci->config->item('kontakt_tel')*/
				),
			);

			$array[$key]['Leistungsdaten'] = array(
				'FoerderfallId' => $leistungsstipendium->buchungsnr,
				'LeistungsdatenId' => $leistungsstipendium->buchungsnr,
				'Leistungsbezeichnung' => $this->_ci->config->item('Leistungsbezeichnung'),
				'Betrag' => $leistungsstipendium->betrag,
				'JahrVon' => $leistungsstipendium->startjahr,
				'JahrBis' => $leistungsstipendium->endjahr,
				'DatumAuszahlung' => $leistungsstipendium->buchungsdatum,
				'Foerdergeber' =>
					array(
						'OkzLst' => $this->_ci->config->item('vkz'),
						'NameLst' => $this->_ci->config->item('name_lst')
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
}