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
		return $flux;
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
	foreach (array(
		'nom' => 'last_name',
		'prenom' => 'first_name',
		'email' => 'email',
		'telephone' => 'phone',
		'adresse' => 'street_address',
		'code_postal' => 'postal_code',
		'ville' => 'city',
		'pays' => 'country'
	) as $spip_field => $distant_field) {
		if (!empty($data[$distant_field])) {
			$flux['data'][$spip_field] = $data[$distant_field];
		}
	}
	if (!empty($data['supplemental_address_1'])) {
		$flux['data']['adresse'] = (!empty($flux['data']['adresse']) ? $flux['data']['adresse'] . "\n" : '') . $data['supplemental_address_1'];
	}
	if (!empty($data['supplemental_address_2'])) {
		$flux['data']['adresse'] = (!empty($flux['data']['adresse']) ? $flux['data']['adresse'] . "\n" : '') . $data['supplemental_address_2'];
	}
	return $flux;
}

function programmer_sync_bank_result($flux) {
	$transaction = null;
	if (!empty($flux['args']['row']) && !empty($flux['args']['row']['id_transaction'])) {
		// on est sur un pipeline qui nous fourni la ligne
		spip_log('programmer_sync_bank_result: la ligne de donnée est dans le flux.', 'campagnodon'._LOG_DEBUG);
		$transaction = $flux['args']['row'];
	} else {
		// il faut aller chercher la transaction en base.
		spip_log('programmer_sync_bank_result: la ligne de donnée n\'est pas dans le flux, je dois chercher en base.', 'campagnodon'._LOG_DEBUG);
		$id_transaction = $flux['args']['id_transaction'];
		if (!$id_transaction) {
			spip_log('id_transaction manquant dans le flux fourni.', 'campagnodon'._LOG_ERREUR);
			return;
		}
		$transaction = sql_fetsel('*', 'spip_transactions', 'id_transaction=' . intval($id_transaction));
		if (!$transaction) {
			spip_log("Transaction introuvable pour id_transaction=".$id_transaction, "campagnodon"._LOG_ERREUR);
			return;
		}
	}
	if ($transaction['parrain'] !== 'campagnodon') {
		// Ça ne concerne pas notre plugin.
		spip_log('programmer_sync_bank_result: cette ligne n\'a pas campagnodon comme parrain, je saute.', 'campagnodon'._LOG_DEBUG);
		return;
	}
	$id_campagnodon_transaction = $transaction['tracking_id'];
	if (!$id_campagnodon_transaction) {
		spip_log("Transaction introuvable pour id_transaction=".$id_transaction.' car pas de tracking_id.', "campagnodon"._LOG_ERREUR);
		return;
	}
	spip_log('programmer_sync_bank_result: je dois programmer une synchronisation de la ligne id_campagnodon_transaction='.$id_campagnodon_transaction, 'campagnodon'._LOG_DEBUG);

	include_spip('inc/campagnodon.utils');
	campagnodon_queue_synchronisation($id_campagnodon_transaction);
}

/**
 * Synchronisation après reglement
 *
 * @pipeline bank_traiter_reglement
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function campagnodon_bank_traiter_reglement($flux) {
	spip_log('Appel de campagnodon_bank_traiter_reglement.', 'campagnodon'._LOG_DEBUG);
	programmer_sync_bank_result($flux);
	return $flux;
}

/**
 * Synchronisation après remboursement
 *
 * @pipeline bank_traiter_remboursement
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function campagnodon_bank_traiter_remboursement($flux) {
	spip_log('Appel de campagnodon_bank_traiter_remboursement.', 'campagnodon'._LOG_DEBUG);
	programmer_sync_bank_result($flux);
	return $flux;
}

/**
 * Synchronisation quand paiement en attente
 *
 * @pipeline trig_bank_reglement_en_attente
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function campagnodon_trig_bank_reglement_en_attente($flux) {
	spip_log('Appel de campagnodon_trig_bank_reglement_en_attente.', 'campagnodon'._LOG_DEBUG);
	programmer_sync_bank_result($flux);
	return $flux;
}


/**
 * Synchronisation après échec de paiement
 *
 * @pipeline trig_bank_reglement_en_echec
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function campagnodon_trig_bank_reglement_en_echec($flux) {
	spip_log('Appel de campagnodon_trig_bank_reglement_en_echec.', 'campagnodon'._LOG_DEBUG);
	programmer_sync_bank_result($flux);
	return $flux;
}
