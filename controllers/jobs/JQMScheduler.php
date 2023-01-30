<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 */
class JQMScheduler extends JQW_Controller
{
	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();

		// Loads JQMSchedulerLib
		$this->load->library('extensions/FHC-Core-TDB/JQMSchedulerLib');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	public function getBPKs()
	{
		$this->logInfo('Start job queue scheduler FHC-Core-TDB->newBPKs');

		// Generates the input for the new job
		$jobInputResult = $this->jqmschedulerlib->getBPKs();

		// If an error occured then log it
		if (isError($jobInputResult))
		{
			$this->logError(getError($jobInputResult));
		}
		else
		{
			// If a job input were generated
			if (hasData($jobInputResult))
			{
				// Add the new job to the jobs queue
				$addNewJobResult = $this->addNewJobsToQueue(
					JQMSchedulerLib::JOB_TYPE_GET_BPKS, // job type
					$this->generateJobs( // gnerate the structure of the new job
						JobsQueueLib::STATUS_NEW,
						getData($jobInputResult)
					)
				);

				// If error occurred return it
				if (isError($addNewJobResult)) $this->logError(getError($addNewJobResult));
			}
			else // otherwise log info
			{
				$this->logInfo('There are no jobs to generate');
			}
		}

		$this->logInfo('End job queue scheduler FHC-Core-SAP->newBPKs');
	}

	public function newFoerderfaelle()
	{
		$this->logInfo('Start job queue scheduler FHC-Core-TDB->newFoerderfaelle');

		// Generates the input for the new job
		$jobInputResult = $this->jqmschedulerlib->newFoerderfaelle();

		// If an error occured then log it
		if (isError($jobInputResult))
		{
			$this->logError(getError($jobInputResult));
		}
		else
		{
			// If a job input were generated
			if (hasData($jobInputResult))
			{
				// Add the new job to the jobs queue
				$addNewJobResult = $this->addNewJobsToQueue(
					JQMSchedulerLib::JOB_TYPE_CREATE_FOERDERFALLE, // job type
					$this->generateJobs( // gnerate the structure of the new job
						JobsQueueLib::STATUS_NEW,
						getData($jobInputResult)
					)
				);

				// If error occurred return it
				if (isError($addNewJobResult)) $this->logError(getError($addNewJobResult));
			}
			else // otherwise log info
			{
				$this->logInfo('There are no jobs to generate');
			}
		}

		$this->logInfo('End job queue scheduler FHC-Core-SAP->newFoerderfaelle');
	}

}
