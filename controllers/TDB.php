<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class TDB extends Auth_Controller
{

	private $_ci;
	private $_uid;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'index'=>'admin:rw',
				'xmlExport' => 'admin:rw',
				'csvExport' => 'admin:rw',
				'csvImport' => 'admin:rw',
				'bpkDetails' => 'admin:rw',
				'saveBPKs' => 'admin:rw'
			)
		);

		$this->_ci =& get_instance();
		$this->_setAuthUID();
		$this->setControllerId(); // sets the controller id

		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBBPKS_model', 'TDBBPKSModel');
		$this->_ci->load->model('person/person_model', 'PersonModel');
		$this->_ci->load->library('AuthLib');
		$this->_ci->load->library('DocumentLib');
		$this->_ci->load->library('WidgetLib');
		$this->_ci->load->library('PhrasesLib');
		$this->_ci->load->library('extensions/FHC-Core-TDB/TDBExportLib');


		$this->_ci->load->helper('hlp_sancho_helper');
		$this->_ci->load->helper('form');
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

	public function xmlExport()
	{
		$exportDate = $this->_ci->input->get('exportDate');
		$testExport = $this->_ci->input->get('bpkExportTest');

		$rootElement = $this->_ci->tdbexportlib->createRootElement();
		$this->_ci->tdbexportlib->createHeaderElement($rootElement, $testExport);
		$this->_ci->tdbexportlib->createBodyElement($rootElement, $exportDate);
		return $this->_ci->tdbexportlib->createXMLExport($exportDate);
	}

	public function csvExport()
	{
		$exportDate = $this->_ci->input->get('csvExportDate');

		if (isEmptyString($exportDate))
			$this->terminateWithJsonError('Fehlerhafte Parameterübergabe');

		$this->_ci->tdbexportlib->createCSVExport($exportDate);
	}

	public function csvImport()
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
	}

	public function saveBPKs()
	{
		$person_id = $this->_ci->input->post('person_id');

		if (!is_numeric($person_id))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$person = $this->_ci->PersonModel->load($person_id);

		if (isError($person))
			$this->terminateWithJsonError(getError($person));

		if (!hasData($person))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$bpkZP = $this->_ci->input->post('bpkZP');
		$bpkAS = $this->_ci->input->post('bpkAS');

		if (isEmptyString($bpkZP) || isEmptyString($bpkAS))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'errorFelderFehlen'));

		$bpks = $this->_ci->TDBBPKSModel->loadWhere(array('person_id' => $person_id));

		if (isError($bpks))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		if (hasData($bpks))
		{
			$bpks = getData($bpks)[0];

			if (!isEmptyString($bpks->vbpk_zp_td) || !isEmptyString($bpks->vbpk_as))
			{
				$this->terminateWithJsonError('BPKs bereits vorhanden');
			}

			$update = $this->_ci->TDBBPKSModel->update(
				array('person_id' => $person_id),
				array('vbpk_zp_td' => rtrim($bpkZP),
					'vbpk_as' => rtrim($bpkAS))
			);

			if (isError($update))
			{
				$this->terminateWithJsonError(getError($update));
			}
		}
		else
		{
			$insert = $this->_ci->TDBBPKSModel->insert(
				array('person_id' => $person_id,
					'vbpk_zp_td' => rtrim($bpkZP),
					'vbpk_as' => rtrim($bpkAS)
				)
			);

			if (isError($insert))
			{
				$this->terminateWithJsonError(getError($insert));
			}
		}

		$this->outputJsonSuccess('Erfolgreich gespeichert!');
	}

	public function bpkDetails()
	{
		$person_id = $this->_ci->input->get('person_id');

		if (!is_numeric($person_id))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$person = $this->_ci->PersonModel->load($person_id);

		if (isError($person))
			$this->terminateWithJsonError(getError($person));

		if (!hasData($person))
			$this->terminateWithJsonError($this->_ci->p->t('ui', 'fehlerBeimLesen'));

		$this->_setNavigationMenuShowDetails();

		$data = array(
			'person_id' => $person_id
		);
		$this->load->view('extensions/FHC-Core-TDB/bpkDetails', $data);
	}

	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();

		if (!$this->_uid) show_error('User authentification failed');
	}

	private function _setNavigationMenuShowDetails()
	{
		$this->load->library('NavigationLib', array('navigation_page' => 'extensions/FHC-Core-TDB/TDB/bpkDetails'));

		$link = site_url('extensions/FHC-Core-TDB/TDB');

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
