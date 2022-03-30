<?php

class TDBBPKS_model extends DB_Model
{
	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'sync.tbl_tdb_bpks';
		$this->pk = array('person_id');
		$this->hasSequence = false;
	}
}