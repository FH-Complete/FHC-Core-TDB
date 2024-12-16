<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class TDB extends Auth_Controller
{
	const EXPORT_DATE = 'exportDate';

	private $_ci;
	private $_uid;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'index'=>'admin:rw',
				/*'xmlExport' => 'admin:rw',
				'csvExport' => 'admin:rw',
				'csvImport' => 'admin:rw',*/
				'bpkDetails' => 'admin:rw',
				'searchBPKs' => 'admin:rw'
			)
		);

		$this->_ci =& get_instance();
		$this->_setAuthUID();
		$this->setControllerId(); // sets the controller id

		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBBPKS_model', 'TDBBPKSModel');
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBExport_model', 'TDBExportModel');
		$this->_ci->load->model('person/person_model', 'PersonModel');
		$this->_ci->load->model('system/Filters_model', 'FiltersModel');

		$this->_ci->load->library('AuthLib');
		$this->_ci->load->library('WidgetLib');
		$this->_ci->load->library('PhrasesLib');
		$this->_ci->load->library('extensions/FHC-Core-TDB/DataManagementLib');

		$this->_ci->load->helper('extensions/FHC-Core-TDB/hlp_tdb_common');
		$this->_ci->load->helper('form');
		
		$this->_ci->load->config('extensions/FHC-Core-TDB/tdb');
		
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

	public function index()
	{
		$date = $this->_ci->input->get('exportDate');
		$csvExportDate = $this->_ci->input->get('csvExportDate');
		
		$semester = $this->_ci->StudiensemesterModel->getLastOrAktSemester();
		$semester = $this->_ci->StudiensemesterModel->getPreviousFrom(getData($semester)[0]->studiensemester_kurzbz);

		if (is_null($date))
			$date = getData($semester)[0]->start;

		if (is_null($csvExportDate))
			$csvExportDate = getData($semester)[0]->start;

		$data = ['date' => $date, 'csvExportDate' => $csvExportDate];

		$this->_ci->load->view('extensions/FHC-Core-TDB/bpkExport', $data);
	}

	/*public function xmlExport()
	{
		$exportDate = $this->_ci->input->get('exportDate');
		$test_export = $this->_ci->input->get('test') === 'true';

		$i = 0;
		do {
			$i++;
			$uebermittlungs_id = $this->_ci->config->item('uebermittlungs_id') . '-' . date('Y-m-d') . '-' . $i;
			$check = $this->_ci->TDBExportModel->loadWhere(array('uebermittlung_id' => $uebermittlungs_id));
		} while(hasData($check));

		$foerderfaelle = $this->_ci->datamanagementlib->getForderfaelleData($exportDate);
		
		if (isError($foerderfaelle))
			$this->terminateWithJsonError('Fehler beim Laden der Foerderfaelle');
		
		$foerderfaelle = getData($foerderfaelle);

		$foerderfaelle_xml_arr = array();

		foreach ($foerderfaelle as $foerderfall)
		{
			if (is_null($foerderfall->vbpk_zp_td) || is_null($foerderfall->vbpk_as))
				continue;

			if (!$test_export)
			{
				$check = $this->_ci->TDBExportModel->loadWhere(array('vorgangs_id' => $foerderfall->buchungsnr));
				
				if (hasData($check))
					continue;
				
				$this->_ci->TDBExportModel->insert(
					array(
						'uebermittlung_id' => $uebermittlungs_id,
						'vorgangs_id' => $foerderfall->buchungsnr
					)
				);
			}

			$foerderfaelle_xml_arr[] = $foerderfall;
		}

		$params = array(
			'uebermittlungs_id' => $uebermittlungs_id,
			'test_export' => $test_export,
			'foerderfaelle' => $foerderfaelle_xml_arr
		);

		$xml_content = $this->load->view('extensions/FHC-Core-TDB/tdbExport', $params, true);
		
		$this->output
			->set_status_header(200)
			->set_content_type('text/xml')
			->set_header('Content-Disposition: attachment; filename="Export_' . $exportDate . '.xml"')
			->set_output($xml_content);
	}*/

	/*public function csvExport()
	{
		$exportDate = $this->_ci->input->get('csvExportDate');

		if (isEmptyString($exportDate))
			$this->terminateWithJsonError('Fehlerhafte Parameterübergabe');
		
		$foerderfaelle = $this->_ci->datamanagementlib->getForderfaelleData($exportDate);
		
		if (!hasData($foerderfaelle))
			show_error('Keine Buchungen gefunden');
		
		//Filenamenkonvention: BPK_<Verwaltungskennzeichen_Org>_<laufnr>.csv
		$filename = "BPK_" .  $this->_ci->config->item('vkz') . "_XX.csv";
		
		header('Content-type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename='.$filename);
		$file = fopen('php://output', 'w');
		
		$csvData = array('LAUFNTR', 'NACHNAME', 'VORNAME', 'GEBDATUM', 'NAME_VOR_ERSTER_EHE', 'GEBORT', 'GESCHLECHT', 'STAATSANGEHÖRIGKEIT',
			'ANSCHRIFTSSTAAT', 'GEMEINDENAME', 'PLZ', 'STRASSE', 'HAUSNR');
		
		fputcsv($file, $csvData, ';');

		foreach (getData($foerderfaelle) as $key => $foerderfall)
		{
			$check = $this->_ci->TDBBPKSModel->loadWhere(array('person_id' => $foerderfall->person_id));
			
			if (!hasData($check))
			{
				$this->_ci->TDBBPKSModel->insert(array('person_id' => $foerderfall->person_id));
				
				$csvRow = array($foerderfall->person_id, $foerderfall->nachname, $foerderfall->vorname, $foerderfall->gebdatum, '',
					$foerderfall->gebort, $foerderfall->geschlecht, $foerderfall->staatsangehoerigkeit, $foerderfall->anschriftsstaat,
					$foerderfall->gemeinde, $foerderfall->plz, '', '');
				
				fputcsv($file, $csvRow, ';');
			}
		}
		fclose($file);
		return $file;
	}*/

	/*public function csvImport()
	{
		if (is_uploaded_file($_FILES['csvFile']['tmp_name']))
		{
			$handle = fopen ($_FILES['csvFile']['tmp_name'],'r');

			$row = 0;
			$error = '';
			while (($data = fgetcsv ($handle, 10000, ";")) !== FALSE)
			{
				$row++;

				if ($row === 1)
					continue;

				$check = $this->_ci->TDBBPKSModel->loadWhere(array('person_id' => $data[0]));

				if (hasData($check))
				{
					$person_id = getData($check)[0]->person_id;
					$this->_ci->TDBBPKSModel->update(array('person_id' => $person_id), array('vbpk_zp_td' => $data[14], 'vbpk_as' => $data[15]));
				}
				else
				{
					$error .= 'Person: ' . $data[0] . ' nicht gefunden;';
				}
			}
			fclose($handle);

			if ($error !== '')
				$this->terminateWithJsonError($error);
			else
				$this->outputJsonSuccess('Erfolgreich hochgeladen');
		}
	}*/

	public function searchBPKs()
	{
		$person_id = $this->_ci->input->post('person_id');

		if (!is_numeric($person_id))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$person = $this->_ci->PersonModel->load($person_id);

		if (isError($person))
			$this->terminateWithJsonError(getError($person));

		if (!hasData($person))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$existsBPKs = $this->_ci->TDBBPKSModel->loadWhere(array('person_id' => $person_id));

		if (isError($existsBPKs))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		if (hasData($existsBPKs))
		{
			$existsBPKsData = getData($existsBPKs)[0];

			if (!isEmptyString($existsBPKsData->vbpk_zp_td) || !isEmptyString($existsBPKsData->vbpk_as))
			{
				$this->terminateWithJsonError('BPKs bereits vorhanden');
			}
		}

		$this->_ci->load->library('extensions/FHC-Core-TDB/SZRApiLib');

		$params = $this->_ci->input->post();
		$bpkResult = $this->_ci->szrapilib->getBPK($person, $params);
		
		if (is_soap_fault($bpkResult))
		{
			if (strpos($bpkResult->faultcode, 'F230') !== false)
			{
				$this->terminateWithJsonError('Es konnte keine Person im ZMR und/oder ERnP gefunden werden.');
			}
			else if ((strpos($bpkResult->faultcode, 'F231') !== false) || strpos($bpkResult->faultcode, 'F233') !== false)
			{
				$this->terminateWithJsonError('Es wurden zu viele Personen im ZMR und/oder ERnP gefunden, so dass das Ergebnis nicht
				eindeutig war. Mit weiteren Suchkriterien kann das Ergebnis noch eindeutig gemacht
				werden.');
			}
			else if (strpos($bpkResult->faultcode, 'pvp:F4') === 0)
			{
				$this->terminateWithJsonError('Fehler beim holen der BPKs');
			}
			else if (isset($bpkResult->faultstring))
			{
				$this->terminateWithJsonError($bpkResult->faultstring);
			}
			else
				$this->terminateWithJsonError('Fehler beim holen der BPKs - kein Faultstring');
		}

		$newBPKs = getBPKFromResponse($bpkResult->FremdBPK);

		if ($newBPKs !== false)
		{
			$result = $this->_ci->datamanagementlib->insertNewBPKs($newBPKs, $person_id, $bpkResult);

			if (isError($result))
				$this->terminateWithJsonError(getError($result));
		}

		$this->outputJsonSuccess('BPKs erfolgreich gespeichert!');
	}

	public function bpkDetails()
	{
		$person_id = $this->_ci->input->get('person_id');

		if (!is_numeric($person_id))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$person = $this->_ci->PersonModel->getPersonStammdaten($person_id, true);

		if (isError($person))
			$this->terminateWithJsonError(getError($person));

		if (!hasData($person))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$this->_setNavigationMenuShowDetails();

		$data = array(
			'person' => getData($person)
		);

		$this->_ci->load->view('extensions/FHC-Core-TDB/bpkDetails', $data);
	}

	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();

		if (!$this->_uid) show_error('User authentification failed');
	}

	private function _setNavigationMenuShowDetails()
	{
		$this->_ci->load->library('NavigationLib', array('navigation_page' => 'extensions/FHC-Core-TDB/TDB/bpkDetails'));

		$link = site_url('extensions/FHC-Core-TDB/TDB');
		
		$currentExportDate = $this->input->get(self::EXPORT_DATE);
		
		if (isset($currentExportDate))
			$link .= '?' . self::EXPORT_DATE . '=' . $currentExportDate;
		
		$this->navigationlib->setSessionMenu(
			array(
				'back' => $this->navigationlib->oneLevel(
					'Zurück',	// description
					$link,			// link
					array(),		// children
					'angle-left',	// icon
					true,			// expand
					null, 			// subscriptDescription
					null, 			// subscriptLinkClass
					null, 			// subscriptLinkValue
					'', 			// target
					1 				// sort
				)
			)
		);
	}
}
