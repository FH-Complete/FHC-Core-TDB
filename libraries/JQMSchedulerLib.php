<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class JQMSchedulerLib
{
	private $_ci; // Code igniter instance

	const JOB_TYPE_GET_BPKS = 'SZRGetBPKs';
	const JOB_TYPE_CREATE_FOERDERFALLE = 'TDBCreateFoerderfaelle';

	/**
	 * Object initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance();
		$this->_ci->load->config('extensions/FHC-Core-TDB/tdb');
	}

	public function getBPKs()
	{
		$jobInput = null;

		$dbModel = new DB_Model();
		$newUsersResult = $dbModel->execReadOnlyQuery('
				SELECT DISTINCT person.person_id
				FROM tbl_person person
				JOIN tbl_konto konto USING (person_id)
				WHERE (konto.buchungstyp_kurzbz IN ? )
					AND konto.buchungsdatum >= ?
					AND 0 = (
						SELECT sum(betrag)
						FROM public.tbl_konto skonto
						WHERE skonto.buchungsnr = konto.buchungsnr_verweis
							OR skonto.buchungsnr_verweis = konto.buchungsnr_verweis
					)
					AND person.person_id NOT IN (SELECT person_id FROM extension.tbl_tdb_bpks)',
				[$this->_ci->config->item('buchungstyp'), $this->_ci->config->item('buchungsdatum')]);

		if (isError($newUsersResult)) return $newUsersResult;

		if (hasData($newUsersResult))
		{
			$jobInput = json_encode(getData($newUsersResult));
		}

		return success($jobInput);
	}

	public function newFoerderfaelle()
	{
		$jobInput = null;

		$dbModel = new DB_Model();

		$newUsersResult = $dbModel->execReadOnlyQuery('
				SELECT DISTINCT person.person_id
				FROM tbl_person person
				JOIN tbl_konto konto USING (person_id)
				WHERE (konto.buchungstyp_kurzbz IN ? )
					AND konto.buchungsdatum >= ?
					AND 0 = (
						SELECT sum(betrag)
						FROM public.tbl_konto skonto
						WHERE skonto.buchungsnr = konto.buchungsnr_verweis
							OR skonto.buchungsnr_verweis = konto.buchungsnr_verweis
					)
					AND konto.buchungsnr NOT IN (SELECT vorgangs_id FROM extension.tbl_tdb_export)',
			[$this->_ci->config->item('buchungstyp'), $this->_ci->config->item('buchungsdatum')]);

		if (isError($newUsersResult)) return $newUsersResult;

		if (hasData($newUsersResult))
		{
			$jobInput = json_encode(getData($newUsersResult));
		}

		return success($jobInput);
	}
}
