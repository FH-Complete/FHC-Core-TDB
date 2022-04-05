<?php
$BUCHUNGSDATUM = "'" . $date ."'";
$LEISTUNGSSTIPENDIUM = '\'Leistungsstipendium\'';
$ZUSCHUSSIO = '\'ZuschussIO\'';

$query = 'SELECT person.vorname AS "Vorname",
			person.nachname AS "Nachname",
			konto.buchungsdatum "Buchungsdatum",
			ABS(konto.betrag) AS "Betrag",
			konto.buchungstyp_kurzbz AS "Buchungstyp",
			konto.buchungsnr AS "VorgangsId",
			konto.buchungsnr AS "FoerderfallId",
			konto.buchungsnr AS "LeistungsdatenId",
			SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 1 ) AS startjahr,
			CONCAT(20, SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 2 )) AS endjahr,
			bpks.vbpk_zp_td AS "TransparentVBK",
			bpks.vbpk_as AS "StatistikAustriaVBK"
		FROM public.tbl_konto konto
		JOIN public.tbl_person person USING (person_id)
		JOIN public.tbl_studiensemester ss ON konto.studiensemester_kurzbz = ss.studiensemester_kurzbz 
		JOIN public.tbl_studienjahr sj ON ss.studienjahr_kurzbz = sj.studienjahr_kurzbz
		LEFT JOIN extension.tbl_tdb_bpks bpks ON person.person_id = bpks.person_id 
		WHERE (konto.buchungstyp_kurzbz = '. $LEISTUNGSSTIPENDIUM. ')
			AND buchungsdatum >= '. $BUCHUNGSDATUM .'
			AND 0 =
			(
				SELECT sum(betrag)
				FROM public.tbl_konto skonto
				WHERE skonto.buchungsnr = konto.buchungsnr_verweis
				OR skonto.buchungsnr_verweis = konto.buchungsnr_verweis
		)
		ORDER BY CASE WHEN bpks.vbpk_zp_td IS NULL THEN 0 ELSE 1 END,
				CASE WHEN bpks.vbpk_as IS NULL THEN 0 ELSE 1 END,
				konto.buchungsdatum DESC';

$filterWidgetArray = array(
	'query' => $query,
	'app' => 'core',
	'datasetName' => 'leistungsstipendium',
	'filter_id' => $this->input->get('filter_id'),
	'requiredPermissions' => 'admin',
	'datasetRepresentation' => 'tablesorter',
	'tableUniqueId' => 'bpkExport',
	'hideOptions' => true,
	'columnsAliases' => array(
		'Vorname',
		'Nachname',
		'Buchungsdatum',
		'Betrag',
		'Buchungstyp',
		'Vorgangs-ID',
		'Förderfall-ID',
		'Leistungsdaten-ID',
		'Zeitpunkt Von',
		'Zeitpunkt Bis'
	),
	'formatRow' => function($datasetRaw) {

		if ($datasetRaw->{'Buchungsdatum'} !== null)
			$datasetRaw->{'Buchungsdatum'} = date_format(date_create($datasetRaw->{'Buchungsdatum'}), 'd.m.Y');

		return $datasetRaw;
	},

	'markRow' => function($datasetRaw) {

		$mark = '';

		if (is_null($datasetRaw->{'TransparentVBK'}) || is_null($datasetRaw->{'StatistikAustriaVBK'}))
		{
			$mark = "text-danger";
		}

		return $mark;
	}
);

echo $this->widgetlib->widget('FilterWidget', $filterWidgetArray);
?>
