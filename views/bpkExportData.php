<?php

$this->load->config('extensions/FHC-Core-TDB/tdb');

$BUCHUNGSDATUM = "'" . $date ."'";
$LEISTUNGSSTIPENDIUM = '\'Leistungsstipendium\'';
$ZUSCHUSSIO = '\'ZuschussIO\'';
$TDB_BPK = '\'vbpkTd\'';
$AS_BPK = '\'vbpkAs\'';

$SZR_ENABLED = $this->config->item('szr_enabled');


if ($SZR_ENABLED)
{
	$query = 'SELECT
			person.person_id as "PersonId",
			person.vorname AS "Vorname",
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
			bpks.vbpk_as AS "StatistikAustriaVBK",
			export.uebermittlung_id AS "Uebermittelt"
		FROM public.tbl_konto konto
		JOIN public.tbl_person person USING (person_id)
		JOIN public.tbl_studiensemester ss ON konto.studiensemester_kurzbz = ss.studiensemester_kurzbz 
		JOIN public.tbl_studienjahr sj ON ss.studienjahr_kurzbz = sj.studienjahr_kurzbz
		LEFT JOIN extension.tbl_tdb_bpks bpks ON person.person_id = bpks.person_id
		LEFT JOIN extension.tbl_tdb_export export ON export.vorgangs_id = konto.buchungsnr
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
}
else
{
	$query = '
		SELECT
			person.person_id as "PersonId",
			person.vorname AS "Vorname",
			person.nachname AS "Nachname",
			konto.buchungsdatum "Buchungsdatum",
			ABS(konto.betrag) AS "Betrag",
			konto.buchungstyp_kurzbz AS "Buchungstyp",
			konto.buchungsnr AS "VorgangsId",
			konto.buchungsnr AS "FoerderfallId",
			konto.buchungsnr AS "LeistungsdatenId",
			SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 1 ) AS startjahr,
			CONCAT(20, SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 2 )) AS endjahr,
			tdbVBK.inhalt AS "TransparentVBK",
			asVBK.inhalt AS "StatistikAustriaVBK",
			export.uebermittlung_id AS "Uebermittelt"
		FROM public.tbl_konto konto
		JOIN public.tbl_person person USING (person_id)
		JOIN public.tbl_studiensemester ss ON konto.studiensemester_kurzbz = ss.studiensemester_kurzbz 
		JOIN public.tbl_studienjahr sj ON ss.studienjahr_kurzbz = sj.studienjahr_kurzbz
		LEFT JOIN public.tbl_kennzeichen tdbVBK ON person.person_id = tdbVBK.person_id AND tdbVBK.kennzeichentyp_kurzbz = ' . $TDB_BPK . ' AND tdbVBK.aktiv
        LEFT JOIN public.tbl_kennzeichen asVBK ON person.person_id = asVBK.person_id AND asVBK.kennzeichentyp_kurzbz = ' . $AS_BPK . ' AND asVBK.aktiv
		LEFT JOIN extension.tbl_tdb_export export ON export.vorgangs_id = konto.buchungsnr
		WHERE (konto.buchungstyp_kurzbz = ' . $LEISTUNGSSTIPENDIUM . ')
			AND buchungsdatum >= ' . $BUCHUNGSDATUM . '
			AND 0 =
			(
				SELECT sum(betrag)
				FROM public.tbl_konto skonto
				WHERE skonto.buchungsnr = konto.buchungsnr_verweis
				OR skonto.buchungsnr_verweis = konto.buchungsnr_verweis
		)
		ORDER BY CASE WHEN tdbVBK.inhalt IS NULL THEN 0 ELSE 1 END,
				CASE WHEN asVBK.inhalt IS NULL THEN 0 ELSE 1 END,
				konto.buchungsdatum DESC

	';
}

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
		'Person-ID',
		'Vorname',
		'Nachname',
		'Buchungsdatum',
		'Betrag',
		'Buchungstyp',
		'Vorgangs-ID',
		'Förderfall-ID',
		'Leistungsdaten-ID',
		'Zeitpunkt Von',
		'Zeitpunkt Bis',
		'TransparentVBK',
		'StatistikAustriaVBK',
		'Übermittelt'
	),
	'formatRow' => function($datasetRaw) {

		if ($datasetRaw->{'Buchungsdatum'} !== null)
			$datasetRaw->{'Buchungsdatum'} = date_format(date_create($datasetRaw->{'Buchungsdatum'}), 'd.m.Y');

		if ($datasetRaw->{'startjahr'} !== null)
			$datasetRaw->{'startjahr'} = (string)(int)$datasetRaw->{'startjahr'} - 1;

		if ($datasetRaw->{'endjahr'} !== null)
			$datasetRaw->{'endjahr'} = (string)(int)$datasetRaw->{'endjahr'} - 1;

		if (($datasetRaw->{'Uebermittelt'} === null))
			$datasetRaw->{'Uebermittelt'} = 'Nein';
		else
			$datasetRaw->{'Uebermittelt'} = 'Ja';

		return $datasetRaw;
	},

	'markRow' => function($datasetRaw) {

		$mark = '';

		if (($datasetRaw->{'TransparentVBK'} === null) || ($datasetRaw->{'StatistikAustriaVBK'} === null))
		{
			$mark = "text-danger";
		}

		return $mark;
	}
);

if ($SZR_ENABLED)
{
	$filterWidgetArray['additionalColumns'] = array('Option');

	$oldFormatRow = $filterWidgetArray['formatRow'];

	$filterWidgetArray['formatRow'] = function($datasetRaw) use ($oldFormatRow)
	{
		$datasetRaw = $oldFormatRow($datasetRaw);

		if (($datasetRaw->{'TransparentVBK'} === null) || ($datasetRaw->{'StatistikAustriaVBK'} === null))
		{
			$datasetRaw->{'Option'} = sprintf(
				'<a href="%s?person_id=%s%s">Details</a>',
				site_url('extensions/FHC-Core-TDB/TDB/bpkDetails'),
				$datasetRaw->{'PersonId'},
				(isset($_GET['exportDate']) ? '&exportDate=' . $_GET['exportDate'] : '')
			);
		}
		else
		{
			$datasetRaw->{'Option'} = '-';
		}

		return $datasetRaw;
	};
}

echo $this->widgetlib->widget('FilterWidget', $filterWidgetArray);
?>
