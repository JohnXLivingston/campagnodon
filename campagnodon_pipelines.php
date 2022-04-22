<?php

/**
 * Divers pipelines définis par le plugin Campagnodon
 * 
 * @plugin Campagnodon
 * @copyright 2022
 * @author John Livingston
 * @licence AGPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Planifier la synchronisation des campagnes.
 * @param $taches_generales
 * @return mixed
 */
function campagnodon_taches_generales_cron($taches_generales) {
	$taches_generales['campagnodon_synchronisation_campagnes'] = 3600; // 1h
	return $taches_generales;
}

function campagnodon_bank_dsp2_renseigner_facturation($flux) {
	if ($flux['args']['parrain'] !== 'campagnodon') {
		// Ça ne concerne pas notre plugin.
		return;
	}
	spip_log('Pipeline bank_dsp2_renseigner_facturation: je dois chercher les infos.', 'campagnodon'._LOG_DEBUG);

	if (
		$id_transaction = $flux['args']['id_transaction']
		and	$campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_transaction='.sql_quote($id_transaction))
	) {
		if ($campagnodon_transaction['type_distant'] === 'civicrm') {
			spip_log('Pipeline bank_dsp2_renseigner_facturation: Je dois chercher les infos sur CiviCRM', 'campagnodon'._LOG_DEBUG);

			$id_contact_distant = $campagnodon_transaction['id_contact_distant'];
			if (!empty($id_contact_distant)) {
				include_spip('inc/civicrm/class.api');
				$civi_api = new civicrm_api3(_CAMPAGNODON_CIVICRM_API_OPTIONS);
				$result = $civi_api->Contact->get([
					'contact_id' => $id_contact_distant
				]);
				if (!$result) {
					spip_log("Erreur CiviCRM->Contact->get: " . $civi_api->errorMsg(), "campagnodon"._LOG_ERREUR);
				} else {
					foreach ($civi_api->values as $civi_contact) {
						spip_log('Pipeline bank_dsp2_renseigner_facturation: J\'ai bien trouvé les informations sur CiviCRM.', 'campagnodon'._LOG_DEBUG);
						$flux['data']['nom'] = $civi_contact->last_name;
						$flux['data']['prenom'] = $civi_contact->first_name;
						$flux['data']['email'] = $civi_contact->email;
						$flux['data']['adresse'] = $civi_contact->street_address;
						$flux['data']['code_postal'] = $civi_contact->postal_code;
						$flux['data']['ville'] = $civi_contact->city;
						$flux['data']['pays'] = $civi_contact->country;
						// spip_log('Réponse Civi: '.var_export($civi_contact, true), 'campagnodon'._LOG_DEBUG);
						// spip_log('Résultat: '.json_encode($flux['data']), 'campagnodon'._LOG_DEBUG);
						return $flux;
					}
				}
			}
		}
	}
	
	spip_log('Pipeline bank_dsp2_renseigner_facturation: Impossible de trouver les informations à fournir.', 'campagnodon'._LOG_ERREUR);
	return $flux;
}
