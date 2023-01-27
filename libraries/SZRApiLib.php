<?php

class SZRApiLib
{

	private $wsdl = APPPATH."extensions/FHC-Core-TDB/config/WSDLs/szr/SZR.wsdl";
	private $client;
	private $_ci;

	public function __construct()
	{
		$this->_ci =& get_instance();
		$this->_ci->load->config('extensions/FHC-Core-TDB/szr');
	}

	public function setSoapClient()
	{
		$options = $this->getOptions();
		$header = $this->getHeader();

		$this->client = new SoapClient($this->wsdl, $options);
		$this->client->__setSoapHeaders($header);
		$this->client->__setLocation($this->_ci->config->item('endpoint'));
	}

	private function getOptions()
	{
		return array(
			'keep_alive' => true,
			'trace' => true,
			'local_cert' => $this->_ci->config->item('cert')
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

	public function getBPK($person)
	{
		$this->setSoapClient();

		$param = [
			'PersonInfo' => [
				'Person' => [
					'Name' => [
						'GivenName' => $person->vorname,
						'FamilyName' => $person->nachname
					],
					'DateOfBirth' => $person->gebdatum,
					'PlaceOfBirth' => $person->gebort,
					//'Sex' => $person->geschlecht,
					'Nationality' => $person->staatsangehoerigkeit
				],
				'RegularDomicile' => [
					'PostalCode' => $person->plz
				]
			],
			/*'BereichsKennung' => 'urn:publicid:gv.at:wbpk+' . $this->_ci->config->item('bereichs_kennung'),
			'VKZ' => $this->_ci->config->item('vkz'),*/
			'Target' => [
				['BereichsKennung' => 'urn:publicid:gv.at:cdid+ZP-TD', 'VKZ' => 'BMF'],
				['BereichsKennung' => 'urn:publicid:gv.at:cdid+AS', 'VKZ' => 'BBA-STA'],
			]
		];

		try
		{
			return ($this->client->{'GetBPK'}($param));
		}
		catch (SoapFault $e)
		{
			return $e;
		}


	}
}