<?php

class SZRApiLib
{

	private $client;
	private $_ci;
	const WSDL_FULL_NAME = APPPATH.'config/'.ENVIRONMENT.'/extensions/FHC-Core-TDB/WSDLs/szr/SZR.wsdl';

	public function __construct()
	{
		$this->_ci =& get_instance();
		$this->_ci->load->config('extensions/FHC-Core-TDB/szr');
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
			'trace' => true,
			'local_cert' => $this->_ci->config->item('cert'),
			'passphrase' => $this->_ci->config->item('passphrase')
		);
	}

	private function getHeader()
	{
		$headerValue = '
			<Security>
				<pvp:pvpToken xmlns:pvp="http://egov.gv.at/pvp1.xsd" version="1.8">
					<pvp:authenticate>
					<pvp:participantId>'.$this->_ci->config->item('participant_id').'</pvp:participantId>
						<pvp:userPrincipal>
							<pvp:cn>'.$this->_ci->config->item('cn').'</pvp:cn>
							<pvp:ou>'.$this->_ci->config->item('ou').'</pvp:ou>
							<pvp:gvOuId>'.$this->_ci->config->item('gv_ou_id').'</pvp:gvOuId>
							<pvp:userId>'.$this->_ci->config->item('user_id').'</pvp:userId>
							<pvp:gvSecClass>'.$this->_ci->config->item('gv_sec_class').'</pvp:gvSecClass>
						</pvp:userPrincipal>
					</pvp:authenticate>
					<pvp:authorize>
						<pvp:role value="'. $this->_ci->config->item('bpk_abfrage_role') .'"/>
					</pvp:authorize>
				</pvp:pvpToken>
			</Security>';

		$headerValue = new SoapVar($headerValue, XSD_ANYXML);

		return new SoapHeader('urn:SZRServices', 'Security', $headerValue);
	}

	private function getParam($person, $param = array())
	{
		if (empty($param))
		{
			$apiParam = [
				'PersonInfo' => [
					'Person' => [
						'Name' => [
							'GivenName' => $person->vorname,
							'FamilyName' => $person->nachname
						],
						'DateOfBirth' => $person->gebdatum
					],
					'RegularDomicile' => [
						'PostalCode' => $person->plz
					]
				],
			];
		}
		else
		{
			$apiParam = [
				'PersonInfo' => [
					'Person' => [
						'Name' => [
							'GivenName' => $param['vorname'],
							'FamilyName' => $param['nachname']
						],
					],
				],
			];

			if (isset($param['geschlecht']))
				$apiParam['PersonInfo']['Person']['Sex'] = $param['geschlecht'];

			if (isset($param['gebort']))
				$apiParam['PersonInfo']['Person']['PlaceOfBirth'] = $param['gebort'];

			if (isset($param['gebdatum']))
				$apiParam['PersonInfo']['Person']['DateOfBirth'] = date('Y-m-d', strtotime($param['gebdatum']));

			if (isset($param['gebnation']))
				$apiParam['PersonInfo']['Person']['CountryOfBirth'] = $param['gebnation'];

			if (isset($param['staatsbuerger']))
				$apiParam['PersonInfo']['Person']['Nationality'] = $param['staatsbuerger'];

			if (isset($param['strasse']))
				$apiParam['PersonInfo']['RegularDomicile']['DeliveryAddress']['StreetName'] = $param['strasse'];

			if (isset($param['plz']))
				$apiParam['PersonInfo']['RegularDomicile']['PostalCode'] = $param['plz'];
		}

		$apiParam['Target' ] = [
			['BereichsKennung' => 'urn:publicid:gv.at:cdid+ZP-TD', 'VKZ' => 'BMF'],
			['BereichsKennung' => 'urn:publicid:gv.at:cdid+AS', 'VKZ' => 'BBA-STA'],
		];

		return $apiParam;
	}

	public function getBPK($person, $param = array())
	{

		$this->setSoapClient();
		$apiParam = $this->getParam($person, $param);

		try
		{
			return $this->client->{'GetBPK'}($apiParam);
		}
		catch (SoapFault $e)
		{
			return $e;
		}
	}
}