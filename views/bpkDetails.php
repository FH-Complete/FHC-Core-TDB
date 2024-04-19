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
			'ui',
			'core'
		),
	)
);
?>

<body>
	<div id="wrapper">
		<?php echo $this->widgetlib->widget('NavigationWidget'); ?>
		<div id="page-wrapper">
			<div class="container-fluid">
				<?php $adresse = $person->adressen[0]; ?>
				<input type="hidden" id="hiddenpersonid" value="<?php echo $person->person_id?>">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header">
							<?php echo  ucfirst($this->p->t('person', 'bpk')) . ' ' . ucfirst($this->p->t('ui', 'suche')); ?>
						</h3>
					</div>
				</div>
				<br/>
				<div class="form-group row">
					<div class="col-sm-4">
						<label for="vorname"><?php echo  ucfirst($this->p->t('person', 'vorname')) ?></label>
						<input type="text" id="vorname" class="form-control needed" value="<?php echo $person->vorname ?>"/>
					</div>
					<div class="col-sm-4">
						<label for="nachname"><?php echo  ucfirst($this->p->t('person', 'nachname')) ?></label>
						<input type="text" id="nachname" class="form-control needed" value="<?php echo $person->nachname ?>"/>
					</div>

					<div class="col-sm-4">
						<label for="geschlecht"><?php echo  ucfirst($this->p->t('person', 'geschlecht')) ?></label>
						<select id="geschlecht" class="form-control needed">
							<option value="male" <?php echo ($person->geschlecht === 'm' ? 'selected' : '')?>><?php echo  ucfirst($this->p->t('person', 'maennlich')) ?></option>
							<option value="female" <?php echo ($person->geschlecht === 'w' ? 'selected' : '')?>><?php echo  ucfirst($this->p->t('person', 'weiblich')) ?></option>
							<option value=""><?php echo '-' ?></option>
						</select>
					</div>

				</div>
				<div class="form-group row">
					<div class="col-sm-4">
						<label for="gebdatum"><?php echo  ucfirst($this->p->t('person', 'geburtsdatum')) ?></label>
						<input type="text" id="gebdatum" class="form-control needed"  value="<?php echo date('d.m.Y', strtotime($person->gebdatum)) ?>"/>
					</div>
					<div class="col-sm-4">
						<label for="gebort"><?php echo  ucfirst($this->p->t('person', 'geburtsort')) ?></label>
						<input type="text" id="gebort" class="form-control needed"  value="<?php echo $person->gebort ?>"/>
					</div>
					<div class="col-sm-4">
						<label for="gebnation"><?php echo  ucfirst($this->p->t('person', 'geburtsnation')) ?></label>
						<input type="text" id="gebnation" class="form-control needed"  value="<?php echo $person->geburtsnation ?>"/>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-sm-4">
						<label for="strasse"><?php echo  ucfirst($this->p->t('person', 'strasse')) ?></label>
						
						<input type="text" id="strasse" class="form-control needed"  value="<?php echo preg_replace('/[^a-zA-ZäöüÄÖÜ]+$/','',$adresse->strasse);  ?>"/>
					</div>
					<div class="col-sm-4">
						<label for="plz"><?php echo  ucfirst($this->p->t('person', 'postleitzahl')) ?></label>
						<input type="text" id="plz" class="form-control needed"  value="<?php echo $adresse->plz ?>"/>
					</div>

					<div class="col-sm-4">
						<label for="staatsbuerger"><?php echo  ucfirst($this->p->t('person', 'staatsbuergerschaft')) ?></label>
						<input type="text" id="staatsbuerger" class="form-control needed"  value="<?php echo $person->staatsbuergerschaft ?>"/>
					</div>
					
				</div>
				
				<div class="form-group row">
					<div class="col-sm-2 text-center">
						<label for="suchen"><?php echo $this->p->t('ui', 'suche'); ?></label>
						<button class="btn btn-default form-control" id="suchen">
							<i class="fa fa-search fa-fw fa-1x" aria-hidden="true"></i>
						</button>
					</div>
				</div>
			</div> <!-- ./container-fluid-->
		</div> <!-- ./page-wrapper-->
	</div> <!-- ./wrapper -->
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
