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
				'dbExecuteUser' => 'Cronjob system',
				'requestId' => 'JOB',
				'requestDataFormatter' => function($data) {
					return json_encode($data);
				}
			),
			'LogLibTDB'
		);

		$this->_ci->load->model('crm/Konto_model', 'KontoModel');
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBBPKS_model', 'TDBBPKSModel');
		$this->_ci->load->library('extensions/FHC-Core-TDB/TDBManagementLib');
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
		$personsAllData = $this->_ci->tdbmanagementlib->getAllPersonsData($persons);

		if (isError($personsAllData)) return $personsAllData;
		if (!hasData($personsAllData)) return error('No data available for the given persons');

		$this->_ci->load->library('extensions/FHC-Core-TDB/SZRApiLib');

		foreach (getData($personsAllData) as $personData)
		{
			// Call to get the BPK for the person
			$bpkResult = $this->_ci->szrapilib->getBPK($personData);

			if (is_soap_fault($bpkResult))
			{
				$this->_ci->LogLibTDB->logWarningDB($bpkResult->faultcode . " Error message '" . $bpkResult->faultstring .  "' person_id: " . $personData->person_id);

				if (strpos($bpkResult->faultcode, 'pvp:F4') === 0)
				{
					return error('Error fetching BPKs');
				}

				continue;
			}

			// Trying to get the needed BPKs
			$zp_td_key = array_search('urn:publicid:gv.at:ecdid+BMF+ZP-TD', array_column($bpkResult->FremdBPK, 'BereichsKennung'));
			$sta_as_key = array_search('urn:publicid:gv.at:ecdid+BBA-STA+AS', array_column($bpkResult->FremdBPK, 'BereichsKennung'));

			// If we got the needed BPKs we store in the database
			if ($zp_td_key !== false && $sta_as_key !== false)
			{
				$insertResult = $this->_ci->TDBBPKSModel->insert([
					'person_id' => $personData->person_id,
					'vbpk_as' => $bpkResult->FremdBPK[$sta_as_key]->FremdBPK,
					'vbpk_zp_td' => $bpkResult->FremdBPK[$zp_td_key]->FremdBPK
				]);
				
				if (isError($insertResult)) return $insertResult;
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