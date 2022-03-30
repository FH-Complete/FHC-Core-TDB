<?php

// Add Menu-Entry to Main Page
$config['navigation_header']['*']['Personen']['children']['TDB'] = array(
	'link' => site_url('extensions/FHC-Core-TDB/TDB'),
	'description' => 'Transparenzdatenbank',
	'expand' => false,
	'requiredPermissions' => 'admin:rw'
);
