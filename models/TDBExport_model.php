<?php

class TDBExport_model extends DB_Model
{
	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_tdb_export';
		$this->pk = array('uebermittlung_id', 'vorgangs_id');
		$this->hasSequence = false;
	}
}
