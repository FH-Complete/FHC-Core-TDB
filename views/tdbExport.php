<?php

$vkz = $this->config->item('vkz')
	?: show_error('Missing config entry for vkz');
$name_lst = $this->config->item('name_lst')
	?: show_error('Missing config entry for name_lst');
$leistungsangebot_id = $this->config->item('leistungsangebot_id')
	?: show_error('Missing config entry for leistungsangebot_id');
$foerdergegenstand = $this->config->item('foerdergegenstand')
	?: show_error('Missing config entry for foerdergegenstand');
$foerderfall_status = $this->config->item('foerderfall_status')
	?: show_error('Missing config entry for foerderfall_status');
$kontakt = $this->config->item('kontakt')
	?: show_error('Missing config entry for kontakt');
$Leistungsbezeichnung = $this->config->item('leistungsbezeichnung')
	?: show_error('Missing config entry for leistungsbezeichnung');

echo '<?xml version="1.0" encoding="UTF-8"?>';
$i = 1;
?>

<UebermittlungFoerderfallLeistungsdaten xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://transparenzportal.gv.at/foerderfallLeistungsdaten">
	<Header>
		<OkzUeb><?php echo $vkz; ?></OkzUeb>
		<NameUeb><?php echo $name_lst; ?></NameUeb>
		<UebermittlungsId><?php echo $uebermittlungs_id; ?></UebermittlungsId>
		<TsErstellung><?php echo date('Y-m-d\TH:i:s'); ?></TsErstellung>
		<Test><?php echo ($test_export ? 'true' : 'false'); ?></Test>
	</Header>
	<?php foreach ($foerderfaelle as $foerderfall): ?>
	<FoerderfallLeistungsdaten Aktion="E" AufruferReferenz="<?php echo $i; ?>">
		<Foerderfall>
			<VorgangsId><?php echo $foerderfall->buchungsnr; ?></VorgangsId>
			<FoerderfallId><?php echo $foerderfall->buchungsnr; ?></FoerderfallId>
			<LeistungsangebotID><?php echo $leistungsangebot_id; ?></LeistungsangebotID>
			<Foerdergegenstand><?php echo $foerdergegenstand; ?></Foerdergegenstand>
			<Status>
				<Datum><?php echo $foerderfall->buchungsdatum; ?></Datum>
				<Status><?php echo $foerderfall_status; ?></Status>
				<Betrag><?php echo $foerderfall->betrag; ?></Betrag>
			</Status>
			<Foerdergeber>
				<OkzLst><?php echo $vkz; ?></OkzLst>
				<NameLst><?php echo $name_lst; ?></NameLst>
			</Foerdergeber>
			<Foerdernehmer>
				<FoerdernehmerNatPers>
					<vbPK_ZP_TD><?php echo $foerderfall->vbpk_zp_td; ?></vbPK_ZP_TD>
					<vbPK_AS><?php echo $foerderfall->vbpk_as; ?></vbPK_AS>
				</FoerdernehmerNatPers>
			</Foerdernehmer>
			<Kontaktinfo>
				<Kontakt><?php echo $kontakt; ?></Kontakt>
			</Kontaktinfo>
		</Foerderfall>
	</FoerderfallLeistungsdaten>
	<?php $i++;
		if($test_export)
			continue;
	?>
	<FoerderfallLeistungsdaten Aktion="E" AufruferReferenz="<?php echo $i; ?>">
		<Leistungsdaten>
			<FoerderfallId><?php echo $foerderfall->buchungsnr; ?></FoerderfallId>
			<LeistungsdatenId><?php echo $foerderfall->buchungsnr; ?></LeistungsdatenId>
			<Leistungsbezeichnung><?php echo $Leistungsbezeichnung; ?></Leistungsbezeichnung>
			<Betrag><?php echo $foerderfall->betrag; ?></Betrag>
			<JahrVon><?php echo $foerderfall->startjahr; ?></JahrVon>
			<JahrBis><?php echo $foerderfall->endjahr; ?></JahrBis>
			<DatumAuszahlung><?php echo $foerderfall->buchungsdatum; ?></DatumAuszahlung>
			<Foerdergeber>
				<OkzLst><?php echo $vkz; ?></OkzLst>
				<NameLst><?php echo $name_lst; ?></NameLst>
			</Foerdergeber>
		</Leistungsdaten>
	</FoerderfallLeistungsdaten>
	<?php
		$i++;
		endforeach;
	?>
</UebermittlungFoerderfallLeistungsdaten>
