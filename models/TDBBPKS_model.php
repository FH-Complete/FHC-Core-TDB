<?php

class TDBBPKS_model extends DB_Model
{
	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_tdb_bpks';
		$this->pk = array('person_id');
		$this->hasSequence = false;
	}
}
