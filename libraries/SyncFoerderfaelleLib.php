<?php

class SyncFoerderfaelleLib
{
	const TDB_CREATE_FOERDERFAELLE = 'TDBCreateFoerderfaelle';

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
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBExport_model', 'TDBExportModel');
		$this->_ci->load->library('extensions/FHC-Core-TDB/TDBManagementLib');
	}

	public function newFoerderfaelle($persons)
	{
		if (isEmptyArray($persons)) return success('No foerderfaell to be created');

		// Retrieves all users data
		$personsAllData = $this->_ci->tdbmanagementlib->getAllPersonsData($persons);

		if (isError($personsAllData)) return $personsAllData;
		if (!hasData($personsAllData)) return error('No data available for the given persons');

		$personsAllData = getData($personsAllData);

		$this->_ci->load->library('extensions/FHC-Core-TDB/TDBApiLib');
		// Preparing the data
		$body = $this->_ci->tdbapilib->prepareFoerderfallBody($personsAllData);
		// Call to create the foerderfall
		$foerderfallResult = $this->_ci->tdbapilib->newFoerderfall($body);

		if (is_soap_fault($foerderfallResult))
		{
			$this->_ci->LogLibTDB->logErrorDB("Error code: " .$foerderfallResult->faultcode . " Error message: '" . $foerderfallResult->faultstring .  "'");
			if (isset($foerderfallResult->detail))
			{
				if (isset($foerderfallResult->detail->ValidationError) && is_array($foerderfallResult->detail->ValidationError))
				{
					foreach ($foerderfallResult->detail->ValidationError as $error)
					{
						$this->_ci->LogLibTDB->logErrorDB("Validation error: " . $error);
					}
				}
				else if (isset($foerderfallResult->detail->ValidationError))
				{
					$this->_ci->LogLibTDB->logErrorDB("Validation error: " . $foerderfallResult->detail->ValidationError);
				}
			}
			return error('Failed to create Foerderfaelle');
		}
		
		// If data structure is not ok
		if (isset($foerderfallResult->Code) && $foerderfallResult->Code !== 2010)
		{
			if (isset($foerderfallResult->SatzFehler) && is_array($foerderfallResult->SatzFehler))
			{
				foreach ($foerderfallResult->SatzFehler as $satzFehler)
				{
					if (isset($satzFehler->FehlercodeText) && is_array($satzFehler->FehlercodeText))
					{
						foreach ($satzFehler->FehlercodeText as $fehlerCodeText)
						{
							$this->log($fehlerCodeText->Fehlercode, $fehlerCodeText->Fehlertext, $satzFehler->FoerderfallId);
						}
					}
					else if (isset($satzFehler->FehlercodeText))
					{
						$this->log($satzFehler->FehlercodeText->Fehlercode, $satzFehler->FehlercodeText->Fehlertext, $satzFehler->FoerderfallId);
					}
				}
			}
			else if (isset($foerderfallResult->SatzFehler))
			{
				if (isset($foerderfallResult->SatzFehler->FehlercodeText) && is_array($foerderfallResult->SatzFehler->FehlercodeText))
				{
					foreach ($foerderfallResult->SatzFehler->FehlercodeText as $fehlerCodeText)
					{
						$this->log($fehlerCodeText->Fehlercode, $fehlerCodeText->Fehlertext, $foerderfallResult->SatzFehler->FoerderfallId);
					}
				}
				else if (isset($foerderfallResult->SatzFehler->FehlercodeText))
				{
					$this->log($foerderfallResult->SatzFehler->FehlercodeText->Fehlercode, $foerderfallResult->SatzFehler->FehlercodeText->Fehlertext, $foerderfallResult->SatzFehler->FoerderfallId);
				}
			}

			if (isset($foerderfallResult->HeaderFehler))
			{
				$this->_ci->LogLibTDB->logWarningDB("Error code: " . $foerderfallResult->HeaderFehler->Fehlercode . " Error message: '" . $foerderfallResult->HeaderFehler->Fehlertext ."' for uebermittlungsid: '" . $foerderfallResult->UebermittlungsId ."'");
			}
			return error('Failed to create Foerderfaelle');
		}

		$testCall = $body['Header']['Test'];

		if ($testCall !== true)
		{
			$uebermittlungid = $body['Header']['UebermittlungsId'];
			$buchungsnr = (array_column($personsAllData, 'buchungsnr'));
			foreach ($buchungsnr as $vorgangsid)
			{
				$insertResult = $this->_ci->TDBExportModel->insert([
					'uebermittlung_id' => $uebermittlungid,
					'vorgangs_id' => $vorgangsid
				]);
				if (isError($insertResult)) return $insertResult;
			}
		}

		return success('Foerderfaelle created successfully');
	}
	
	private function log($fehlerCode, $fehlerText, $foerderfall)
	{
		$this->_ci->LogLibTDB->logWarningDB("Error code: " . $fehlerCode . " Error message: '" . $fehlerText ."' for buchungsnr: '" . $foerderfall . "'");
		
	}
}