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

/**
 * Renseigner les infos nominatives/adresses liees a une demande de paiement
 * @param $flux
 * @return mixed
 */
function campagnodon_bank_dsp2_renseigner_facturation($flux) {
	if ($flux['args']['parrain'] !== 'campagnodon') {
		// Ça ne concerne pas notre plugin.
		return;
	}
	spip_log('Pipeline bank_dsp2_renseigner_facturation: je dois chercher les infos.', 'campagnodon'._LOG_DEBUG);

	if (!(
		$id_transaction = $flux['args']['id_transaction']
		and	$campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_transaction='.sql_quote($id_transaction))
	)) {
		spip_log('Pipeline bank_dsp2_renseigner_facturation: Impossible de trouver la transaction campagnodon.', 'campagnodon'._LOG_ERREUR);
		return $flux;
	}

	include_spip('inc/campagnodon.utils');
	$dsp2_renseigner_facturation = campagnodon_fonction_connecteur($campagnodon_transaction['mode'], 'dsp2_renseigner_facturation');
	if (!$dsp2_renseigner_facturation) {
		spip_log('Pipeline bank_dsp2_renseigner_facturation: connecteur dsp2_renseigner_facturation non trouvé pour le mode '.$campagnodon_transaction['mode'].'.', 'campagnodon'._LOG_ERREUR);
		return $flux;
	}

	spip_log('Pipeline bank_dsp2_renseigner_facturation: Connecteur dsp2_renseigner_facturation trouvé.', 'campagnodon'._LOG_DEBUG);
	$mode_options = campagnodon_mode_options($campagnodon_transaction['mode']);
	$data = $dsp2_renseigner_facturation($mode_options, $campagnodon_transaction['transaction_distant']);

	if (!$data) {
		spip_log('Pipeline bank_dsp2_renseigner_facturation: Impossible de trouver les informations à fournir.', 'campagnodon'._LOG_ERREUR);
		return $flux;
	}

	spip_log('Pipeline bank_dsp2_renseigner_facturation: j\'ai pu trouver des informations.', 'campagnodon'._LOG_DEBUG);
	foreach (array('nom', 'prenom', 'email', 'adresse', 'code_postal', 'ville', 'pays') as $attr) {
		if (!empty($data[$attr])) {
			$flux['data'][$attr] = $data[$attr];
		}
	}
	return $flux;
}

function sync_bank_result($flux) {
	if ($flux['args']['row']['parrain'] !== 'campagnodon') {
		// Ça ne concerne pas notre plugin.
		return;
	}
	spip_log('Pipeline bank_dsp2_renseigner_facturation: je dois marquer une ligne comme «à synchroniser».', 'campagnodon'._LOG_DEBUG);
	// TODO: faire la fonction
	spip_log('Not Implemented Yet', 'campagnodon'._LOG_ERREUR);
}

/**
 * Synchronisation après reglement
 *
 * @pipeline bank_traiter_reglement
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function campagnodon_bank_traiter_reglement($flux) {
	sync_bank_result($flux);
}


/**
 * Synchronisation quand paiement en attente
 *
 * @pipeline trig_bank_reglement_en_attente
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function campagnodon_trig_bank_reglement_en_attente($flux) {
	sync_bank_result($flux);
}


/**
 * Synchronisation après échec de paiement
 *
 * @pipeline trig_bank_reglement_en_echec
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function campagnodon_trig_bank_reglement_en_echec($flux) {
	sync_bank_result($flux);
}


// TODO: gérer le pipeline bank_traiter_remboursement ?
