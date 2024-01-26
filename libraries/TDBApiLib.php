<?php

class TDBApiLib
{

	private $client;
	private $_ci;

	const WSDL_FULL_NAME = APPPATH.'config/'.ENVIRONMENT.'/extensions/FHC-Core-TDB/WSDLs/tdb/foerderfallLeistungsdaten.wsdl';

	public function __construct()
	{
		$this->_ci =& get_instance();
		$this->_ci->load->config('extensions/FHC-Core-TDB/tdb');
	}

	public function setSoapClient()
	{
		$options = $this->getOptions();
		$header = $this->getHeader();

		$this->client = new SoapClient(self::WSDL_FULL_NAME, $options);
		$this->client->__setSoapHeaders($header);
		$this->client->__setLocation($this->_ci->config->item('endpoint'));
	}

	private function getOptions()
	{
		return array(
			'keep_alive' => true,
			'trace' => true
		);
	}

	private function getHeader()
	{
		$wssNamespace = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd";

		$username = new SoapVar($this->_ci->config->item('username'), XSD_STRING, null, null, 'Username', $wssNamespace);
		$password = new SoapVar($this->_ci->config->item('password'), XSD_STRING, null, null, 'Password', $wssNamespace);
		$usernameToken = new SoapVar(array($username, $password), SOAP_ENC_OBJECT, null, null, 'UsernameToken', $wssNamespace);
		$usernameToken = new SoapVar(array($usernameToken), SOAP_ENC_OBJECT, null, null, null, $wssNamespace);

		return new SoapHeader($wssNamespace, 'Security', $usernameToken);
	}

	public function newFoerderfall($body)
	{
		$this->setSoapClient();

		try
		{
			return ($this->client->{'FoerderfallLeistungsdatenUebermittlung'}($body));
		}
		catch (SoapFault $e)
		{
			return $e;
		}
	}

	public function prepareFoerderfallBody($personData)
	{
		if ($this->_ci->config->item('test_foerderfall') === true)
		{
			$uebermittlungsID = $this->_ci->config->item('test_uebermittlungsid');
		}
		else
		{
			$i = 0;
			do {
				$i++;
				$uebermittlungsID = $this->_ci->config->item('uebermittlungs_id'). date('Y-m-d') . '-' . $i;
				$check = $this->_ci->TDBExportModel->loadWhere(array('uebermittlung_id' => $uebermittlungsID));
			} while(hasData($check));
		}

		$body['UebermittlungFoerderfallLeistungsdaten'] = array
		(
			'Header' => array(
				'OkzUeb' => $this->_ci->config->item('vkz'),
				'NameUeb' => $this->_ci->config->item('name_lst'),
				'UebermittlungsId' => $uebermittlungsID,
				'TsErstellung' => date('Y-m-d\TH:i:s'),
				'Test' => $this->_ci->config->item('test_foerderfall')
			)
		);

		$aufruferReferenzVal = 1;

		foreach($personData as $leistungsstipendium)
		{
			if (!isset($leistungsstipendium->vbpk_zp_td) || !isset($leistungsstipendium->vbpk_as))
			{
				$this->_ci->LogLibTDB->logWarningDB('No BPK available for the given user: ' . $leistungsstipendium->person_id);
				continue;
			}

			$body['UebermittlungFoerderfallLeistungsdaten']['FoerderfallLeistungsdaten'][] = array
			(
				'Aktion' => 'E',
				'AufruferReferenz' => $aufruferReferenzVal,
				'Foerderfall' => array
				(
					'VorgangsId' => $leistungsstipendium->buchungsnr,
					'FoerderfallId' => $leistungsstipendium->buchungsnr,
					'LeistungsangebotID' => $this->_ci->config->item('leistungsangebot_id'),
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
						'Kontakt' => $this->_ci->config->item('kontakt')
					),
				),
			);

			if ($this->_ci->config->item('test_foerderfall') === true)
				continue;

			$aufruferReferenzVal++;

			$body['UebermittlungFoerderfallLeistungsdaten']['FoerderfallLeistungsdaten'][] = array
			(
				'Aktion' => 'E',
				'AufruferReferenz' => $aufruferReferenzVal,
				'Leistungsdaten' => array
				(
					'FoerderfallId' => $leistungsstipendium->buchungsnr,
					'LeistungsdatenId' => $leistungsstipendium->buchungsnr,
					'Leistungsbezeichnung' => $this->_ci->config->item('leistungsbezeichnung'),
					'Betrag' => $leistungsstipendium->betrag,
					'JahrVon' => $leistungsstipendium->startjahr,
					'JahrBis' => $leistungsstipendium->endjahr,
					'DatumAuszahlung' => $leistungsstipendium->buchungsdatum,
					'Foerdergeber' =>
						array(
							'OkzLst' => $this->_ci->config->item('vkz'),
							'NameLst' => $this->_ci->config->item('name_lst')
						)
				),
			);
			$aufruferReferenzVal++;
		}
		return $body['UebermittlungFoerderfallLeistungsdaten'];
	}
}