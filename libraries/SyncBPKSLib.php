<?php

class SyncBPKSLib
{
	const SZR_GET_BPKS = 'SZRGetBPKs';

	private $_ci;

	public function __construct()
	{
		$this->_ci =& get_instance();

		$this->_ci->load->library(
			'LogLib',
			array(
				'classIndex' => 3,
				'functionIndex' => 3,
				'lineIndex' => 2,
				'dbLogType' => 'job', // required
				'dbExecuteUser' => 'Jobs queue system',
				'requestId' => 'JQW',
				'requestDataFormatter' => function($data) {
					return json_encode($data);
				}
			),
			'LogLibTDB'
		);

		$this->_ci->load->model('crm/Konto_model', 'KontoModel');
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBBPKS_model', 'TDBBPKSModel');
		$this->_ci->load->library('extensions/FHC-Core-TDB/DataManagementLib');
	}
	
	/**
	 * Get BPKs
	 * @param $persons
	 * @return array|stdClass
	 */
	public function getBPKs($persons)
	{
		if (isEmptyArray($persons)) return success('No BPKs needed');

		// Retrieves all users data
		$personsAllData = $this->_ci->datamanagementlib->getPersonsDataForBPK($persons);

		if (isError($personsAllData)) return $personsAllData;
		if (!hasData($personsAllData)) return error('No data available for the given persons');

		$this->_ci->load->library('extensions/FHC-Core-TDB/SZRApiLib');

		foreach (getData($personsAllData) as $personData)
		{
			// Call to get the BPK for the person
			$bpkResult = $this->_ci->szrapilib->getBPK($personData);

			if (is_soap_fault($bpkResult))
			{
				if (strpos($bpkResult->faultcode, 'pvp:F4') === 0)
				{
					return error("Error fetching BPKs. Faultcode: '". $bpkResult->faultcode . "' Error message: '" . $bpkResult->faultstring . "'");
				}

				$this->_ci->LogLibTDB->logWarningDB("Faultcode: '" . $bpkResult->faultcode . "' Error message: '" . $bpkResult->faultstring .  "' person_id: " . $personData->person_id);

				continue;
			}

			$newBPKs = getBPKFromResponse($bpkResult->FremdBPK);

			if ($newBPKs !== false)
			{
				$result = $this->_ci->datamanagementlib->insertNewBPKs($newBPKs, $personData->person_id, $bpkResult);
				
				if (isError($result))
					return $result;
			}
			else
			{
				// Should never happen, just to be sure
				$this->_ci->LogLibTDB->logWarningDB("Was not possible to retrieve the needed BPKs for the person_id: " . $personData->person_id);
			}
		}
		return success('Fetched BPKs successfully');
	}
}