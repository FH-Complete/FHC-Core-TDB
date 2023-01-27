<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

// ------------------------------------------------------------------------
// Collection of utility functions for general purpose
// ------------------------------------------------------------------------

/**
 * Gets a list of jobs as parameter and returns a merged array of person ids
 */
function mergeUsersPersonIdArray($jobs, $jobsAmount = 99999)
{
	$jobsCounter = 0;
	$mergedUsersArray = array();

	// If no jobs then return an empty array
	if (count($jobs) == 0) return $mergedUsersArray;

	// For each job
	foreach ($jobs as $job)
	{
		// Decode the json input
		$decodedInput = json_decode($job->input);

		// If decoding was fine
		if ($decodedInput != null)
		{
			// For each element in the array
			foreach ($decodedInput as $el)
			{
				$mergedUsersArray[] = $el->person_id; //
			}
		}

		$jobsCounter++; // jobs counter

		if ($jobsCounter >= $jobsAmount) break; // if the required amount is reached then exit
	}

	return $mergedUsersArray;
}

