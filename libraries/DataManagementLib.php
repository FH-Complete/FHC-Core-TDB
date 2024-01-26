<?php

class DataManagementLib
{

	private $_ci;

	public function __construct()
	{
		$this->_ci =& get_instance();

		$this->_ci->load->model('extensions/FHC-Core-TDB/TDBBPKS_model', 'TDBBPKSModel');
		$this->_ci->load->model('crm/Konto_model', 'KontoModel');

		$this->_ci->load->config('extensions/FHC-Core-TDB/tdb');
	}

	public function getAllPersonsData($persons)
	{
		$personsALlData = [];

		$dbModel = new DB_Model();

		$dbPersonData = $dbModel->execReadOnlyQuery('
			SELECT DISTINCT p.person_id,
				p.nachname AS nachname,
				p.vorname AS vorname,
				p.gebdatum AS gebdatum,
				p.gebort AS gebort
			  FROM public.tbl_person p
			 WHERE p.person_id IN ?
			', [$persons]
		);

		if (isError($dbPersonData)) return $dbPersonData;
		if (!hasData($dbPersonData)) return error('The provided person ids are not present in database');

		foreach (getData($dbPersonData) as $person)
		{
			$personAllData = $person;

			$this->_ci->load->model('person/Adresse_model', 'AdresseModel');
			$this->_ci->AdresseModel->addSelect('s.iso3166_1_a3 as staatsangehoerigkeit');
			$this->_ci->AdresseModel->addSelect('tbl_adresse.*');
			$this->_ci->AdresseModel->addJoin('bis.tbl_nation s', 'public.tbl_adresse.nation = s.nation_code');
			$this->_ci->AdresseModel->addJoin('bis.tbl_nation a', 'public.tbl_adresse.nation = a.nation_code');
			$this->_ci->AdresseModel->addOrder('updateamum', 'DESC');
			$this->_ci->AdresseModel->addOrder('insertamum', 'DESC');
			$this->_ci->AdresseModel->addLimit(1);
			$addressResult = $this->_ci->AdresseModel->loadWhere(
				['person_id' => $personAllData->person_id, 'zustelladresse' => true]
			);

			if (isError($addressResult)) return $addressResult;
			if (hasData($addressResult))
			{
				$personAllData->plz = getData($addressResult)[0]->plz;
				$personAllData->staatsangehoerigkeit = getData($addressResult)[0]->staatsangehoerigkeit;
			}

			$this->_ci->load->model('person/Konto_model', 'KontoModel');
			$this->_ci->KontoModel->addJoin('public.tbl_studiensemester ss', 'tbl_konto.studiensemester_kurzbz = ss.studiensemester_kurzbz');
			$this->_ci->KontoModel->addJoin('public.tbl_studienjahr sj', 'ss.studienjahr_kurzbz = sj.studienjahr_kurzbz');
			$this->_ci->KontoModel->addSelect('SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 1 ) as startjahr,
										CONCAT(20, SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 2 )) as endjahr,
										tbl_konto.*,
										ABS(betrag) AS betrag');

			$buchungstypen = implode("','", ($this->_ci->config->item('buchungstyp')));
			$kontoResult = $this->_ci->KontoModel->loadWhere("
				(tbl_konto.buchungstyp_kurzbz IN ('". $buchungstypen ."'))
					AND tbl_konto.buchungsdatum >= " . $this->_ci->KontoModel->escape($this->_ci->config->item('buchungsdatum')). "
					AND 0 = (
						SELECT sum(betrag)
						FROM public.tbl_konto skonto
						WHERE skonto.buchungsnr = tbl_konto.buchungsnr_verweis
							OR skonto.buchungsnr_verweis = tbl_konto.buchungsnr_verweis
					)
					AND tbl_konto.buchungsnr NOT IN (SELECT vorgangs_id FROM extension.tbl_tdb_export)
					AND tbl_konto.person_id = " . $personAllData->person_id
			);

			if (isError($kontoResult)) return $kontoResult;
			if (hasData($kontoResult))
			{
				$personAllData->buchungsnr = getData($kontoResult)[0]->buchungsnr;
				$personAllData->buchungsdatum = getData($kontoResult)[0]->buchungsdatum;
				$personAllData->betrag = getData($kontoResult)[0]->betrag;
				$personAllData->startjahr = (string)((int)getData($kontoResult)[0]->startjahr - 1);
				$personAllData->endjahr = (string)((int)getData($kontoResult)[0]->endjahr - 1);
			}

			$bpkResult = $this->_ci->TDBBPKSModel->loadWhere(['person_id' => $personAllData->person_id]);

			if (isError($bpkResult)) return $bpkResult;
			if (hasData($bpkResult))
			{
				$personAllData->vbpk_zp_td = getData($bpkResult)[0]->vbpk_zp_td;
				$personAllData->vbpk_as = getData($bpkResult)[0]->vbpk_as;
			}

			$personsALlData[] = $personAllData;
		}
		return success($personsALlData);
	}

	public function getForderfaelleData($exportDate)
	{
		$this->_ci->KontoModel->addJoin('public.tbl_person', 'person_id');
		$this->_ci->KontoModel->addJoin('public.tbl_adresse', 'person_id');
		$this->_ci->KontoModel->addJoin('bis.tbl_nation s', 'staatsbuergerschaft = s.nation_code');
		$this->_ci->KontoModel->addJoin('bis.tbl_nation a', 'nation = a.nation_code');
		$this->_ci->KontoModel->addJoin('public.tbl_studiensemester ss', 'tbl_konto.studiensemester_kurzbz = ss.studiensemester_kurzbz');
		$this->_ci->KontoModel->addJoin('public.tbl_studienjahr sj', 'ss.studienjahr_kurzbz = sj.studienjahr_kurzbz');
		$this->_ci->KontoModel->addJoin('extension.tbl_tdb_bpks bpks', 'bpks.person_id = tbl_person.person_id', 'LEFT');
		$this->_ci->KontoModel->addSelect('SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 1 ) as startjahr,
										CONCAT(20, SPLIT_PART(sj.studienjahr_kurzbz, \'/\', 2 )) as endjahr,
										matr_nr,
										tbl_konto.*,
										ABS(betrag) AS betrag,
										tbl_person.*,
										s.iso3166_1_a3 AS staatsangehoerigkeit,
										a.iso3166_1_a3 AS anschriftsstaat,
										tbl_adresse.*, bpks.*,
										tbl_person.person_id as person_id');
		$buchungstypen = implode("','", ($this->_ci->config->item('buchungstyp')));

		$where = "(tbl_konto.buchungstyp_kurzbz IN ('".$buchungstypen."'))
			AND tbl_adresse.zustelladresse = true
			AND tbl_konto.buchungsdatum >= " . $this->_ci->KontoModel->escape($exportDate). "
			AND 0 = (
				SELECT sum(betrag)
				FROM public.tbl_konto skonto
				WHERE skonto.buchungsnr = tbl_konto.buchungsnr_verweis
					OR skonto.buchungsnr_verweis = tbl_konto.buchungsnr_verweis
			)";
		return $this->_ci->KontoModel->loadWhere($where);
	}
	
	public function insertNewBPKs($newBPKs, $person_id, $bpkResult)
	{
		$existsBPKs = $this->_ci->TDBBPKSModel->loadWhere(array('person_id' => $person_id));
		
		if (isError($existsBPKs)) return $existsBPKs;
		
		if (hasData($existsBPKs))
		{
			$existsBPKsData = getData($existsBPKs)[0];
			
			if (!isEmptyString($existsBPKsData->vbpk_zp_td) || !isEmptyString($existsBPKsData->vbpk_as))
			{
				$this->_ci->LogLibTDB->logWarningDB("BPKs already exists person_id: " . $person_id);
			}
			else
			{
				return $this->_ci->TDBBPKSModel->update(
					array('person_id' => $person_id),
					array('vbpk_zp_td' => $bpkResult->FremdBPK[$newBPKs['vbpk_zp_td']]->FremdBPK,
						'vbpk_as' => $bpkResult->FremdBPK[$newBPKs['vbpk_as']]->FremdBPK));
				
			}
		}
		else
		{
			return $this->_ci->TDBBPKSModel->insert(
				array('person_id' => $person_id,
					'vbpk_zp_td' => $bpkResult->FremdBPK[$newBPKs['vbpk_zp_td']]->FremdBPK,
					'vbpk_as' => $bpkResult->FremdBPK[$newBPKs['vbpk_as']]->FremdBPK
				)
			);
			
		}
		
	}

}