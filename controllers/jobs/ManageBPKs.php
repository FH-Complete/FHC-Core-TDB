<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class ManageBPKs extends JQW_Controller
{
	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();

		$this->load->helper('extensions/FHC-Core-TDB/hlp_tdb_common');
		$this->load->library('extensions/FHC-Core-TDB/SyncBPKSLib');
	}

	public function getBPKs()
	{
		$this->logInfo('Start data synchronization with SZR: get BPKs');

		// Gets the latest jobs
		$lastJobs = $this->getLastJobs(SyncBPKSLib::SZR_GET_BPKS);

		if (isError($lastJobs))
		{
			$this->logError(getCode($lastJobs).': '.getError($lastJobs), SyncBPKSLib::SZR_GET_BPKS);
		}
		else
		{
			// Get all the jobs in the queue
			$syncResult = $this->syncbpkslib->getBPKs(mergeUsersPersonIdArray(getData($lastJobs)));

			// Log the result
			if (isError($syncResult))
			{
				$this->logError(getCode($syncResult).': '.getError($syncResult));
			}
			else
			{
				$this->logInfo(getData($syncResult));
			}

			// Update jobs properties values
			$this->updateJobs(
				getData($lastJobs), // Jobs to be updated
				array(JobsQueueLib::PROPERTY_STATUS, JobsQueueLib::PROPERTY_END_TIME), // Job properties to be updated
				array(JobsQueueLib::STATUS_DONE, date('Y-m-d H:i:s')) // Job properties new values
			);

			if (hasData($lastJobs)) $this->updateJobsQueue(SyncBPKSLib::SZR_GET_BPKS, getData($lastJobs));
		}

		$this->logInfo('End data synchronization with SZR: get BPKs');
	}
}