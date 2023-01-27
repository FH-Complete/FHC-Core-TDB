<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'BPKExport',
		'jquery3' => true,
		'jqueryui1' => true,
		'bootstrap3' => true,
		'fontawesome4' => true,
		'sbadmintemplate3' => true,
		'tablesorter2' => true,
		'ajaxlib' => true,
		'filterwidget' => true,
		'navigationwidget' => true,
		'dialoglib' => true,
		'tabulator4' => true,
		'customJSs' => array('public/extensions/FHC-Core-TDB/js/BPKExport.js', 'public/js/bootstrapper.js'),
		'customCSSs' => array('public/css/sbadmin2/tablesort_bootstrap.css'),
		'phrases' => array(
			'ui'
		),
	)
);
?>
<body>
<div id="wrapper">
	<?php echo $this->widgetlib->widget('NavigationWidget'); ?>
	<div id="page-wrapper">
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-6">
					<h3 class="page-header">
						TDB XML-Export
						<?php /*echo $this->p->t('core', 'xmlexport') */?>
					</h3>
				</div>
				<div class="col-lg-6">
					<h3 class="page-header">
						<?php /*echo $this->p->t('core', 'CSVexport') */?>
						bPK CSV-Export/Import
					</h3>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-lg-6">
					<form id="getExportDate" method="GET">
						<label for="exportDate" class="col-lg-2 col-form-label">Buchungsdatum</label>
						<div class="col-lg-4">
							<input class="form-control datepicker" type="date" id="exportDate" name="exportDate" value="<?php echo $date; ?>"/>
						</div>
						<div class="col-lg-4">
							<button id="bpkAnzeigen" type="submit" class="btn btn-default"><?php echo ucfirst($this->p->t('ui', 'anzeigen')); ?></button>
						</div>
					</form>
				</div>
				<div class="form-group col-lg-6">
					<label for="csvExportDate" class="col-lg-2 col-form-label">Buchungsdatum</label>
					<div class="col-lg-4">
						<input class="form-control datepicker" type="date" id="csvExportDate" name="csvExportDate" value="<?php echo $csvExportDate; ?>"/>
					</div>
					<div class="col-lg-4">
						<button id="csvExport" type="button" class="btn btn-default">Export</button>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-lg-6">
					<label for="exportDate" class="col-lg-2 col-form-label">Test-Upload</label>
					<div class="col-lg-4">
						<select id="bpkExportTest">
							<option value="false">Nein</option>
							<option value="true">Ja</option>
						</select>
					</div>
					<div class="col-lg-4">
						<button id="bpkExport" type="button" class="btn btn-default">Export</button>
					</div>
				</div>
				<div class="form-group col-lg-6">
					<form id="importCSV" method="POST" enctype="multipart/form-data">
						<div class="col-lg-6">
							<div class="form-control">
								<?php echo form_upload(array(
									'name' => 'uploadfile',
									'accept' => '.csv',
									'size' => '1',
									'required' => 'required',
									'enctype' => "multipart/form-data"
								)); ?>
							</div>
							<a class="pull-right" id="csvFile"></a>
						</div>
						<div class="col-lg-4">
							<button type="submit" class="btn btn-default">Import</button>
						</div>
					</form>

				</div>
			</div>
			<div class="row col-lg-12">
				<?php $this->load->view('extensions/FHC-Core-TDB/bpkExportData.php'); ?>
			</div>
		</div>
	</div>
</div>
</body>
<?php $this->load->view('templates/FHC-Footer'); ?>
