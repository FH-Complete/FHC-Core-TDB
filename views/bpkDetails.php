<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'BPKDetails',
		'jquery3' => true,
		'jqueryui1' => true,
		'bootstrap3' => true,
		'fontawesome4' => true,
		'sbadmintemplate3' => true,
		'dialoglib' => true,
		'ajaxlib' => true,
		'navigationwidget' => true,
		'customJSs' => array('public/extensions/FHC-Core-TDB/js/BPKDetails.js'),
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
				<input type="hidden" id="hiddenpersonid" value="<?php echo $person_id?>">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header">
							BPKS hinzufügen
						</h3>
					</div>
				</div>
				<br/>
				<div class="form-group row">
					<div class="col-sm-4">
						<label for="bpkZP">vbPK-ZP-TD</label>
						<textarea class="form-control" rows="5" id="bpkZP"></textarea>
					</div>
					<div class="col-sm-4">
						<label for="bpkAS">vbPK-AS</label>
						<textarea class="form-control" rows="5" id="bpkAS"></textarea>
					</div>

					<div class="col-sm-1 text-center">
						<label for="speichern"><?php echo $this->p->t('ui', 'speichern'); ?></label>
						<button class="btn btn-default form-control" id="speichern">
							<i class="fa fa-floppy-o fa-fw fa-1x" aria-hidden="true"></i>
						</button>
					</div>

				</div>
			</div> <!-- ./container-fluid-->
		</div> <!-- ./page-wrapper-->
	</div> <!-- ./wrapper -->
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
