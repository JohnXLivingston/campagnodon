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
 * Cette fonction retourne la transaction SPIP Bank, sous réserve que son parrain soit bien Campagnodon.
 * @param $id_transaction
 * @param $caller Nom de la fonction appellante. Sert pour les logs générés.
 * @return $transaction
 */
function _campagnodon_get_bank_transaction($id_transaction, $caller) {
	if (!$id_transaction) {
		spip_log('_campagnodon_get_bank_transaction: id_transaction est falsey. Caller='.$caller, 'campagnodon'._LOG_ERREUR);
		return null;
	}
	$transaction = sql_fetsel('*', 'spip_transactions', 'id_transaction=' . intval($id_transaction));
	if (!$transaction) {
		spip_log("_campagnodon_get_bank_transaction: Transaction introuvable pour id_transaction=".$id_transaction.'. Caller='.$caller, "campagnodon"._LOG_ERREUR);
		return null;
	}
	if ($transaction['parrain'] !== 'campagnodon') {
		// Ça ne concerne pas notre plugin.
		spip_log('_campagnodon_get_bank_transaction: programmer_sync_bank_result: cette ligne n\'a pas campagnodon comme parrain. Caller='.$caller, 'campagnodon'._LOG_DEBUG);
		return null;
	}
	return $transaction;
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
		if ($transaction['parrain'] !== 'campagnodon') {
			// Ça ne concerne pas notre plugin.
			spip_log('programmer_sync_bank_result: cette ligne n\'a pas campagnodon comme parrain, je saute.', 'campagnodon'._LOG_DEBUG);
			return;
		}
	} else {
		// il faut aller chercher la transaction en base.
		spip_log('programmer_sync_bank_result: la ligne de donnée n\'est pas dans le flux, je dois chercher en base.', 'campagnodon'._LOG_DEBUG);
		$id_transaction = $flux['args']['id_transaction'];
		$transaction = _campagnodon_get_bank_transaction($id_transaction, __FUNCTION__);
		if (!$transaction) { return; }
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

/**
 * Decrire l'echeance d'une souscription mensuelle
 * @param array $flux
 * @return array
 */
function campagnodon_bank_abos_decrire_echeance($flux){
	$id_transaction = $flux['args']['id_transaction'];
	$transaction = _campagnodon_get_bank_transaction($id_transaction, __FUNCTION__);
	if (!$transaction) { return $flux; }

	spip_log('campagnodon_bank_abos_decrire_echeance: campagnodon doit gérer', 'campagnodon'._LOG_DEBUG);

	$flux['data']['montant'] = $transaction['montant'];
	$flux['data']['montant_init'] = $transaction['montant'];
	$flux['data']['count_init'] = 0;
	$flux['data']['count'] = 0;
	$flux['data']['freq'] = 'monthly';
	$flux['data']['date_start'] = '';
	spip_log('campagnodon_bank_abos_decrire_echeance: voici les infos remontées: '.print_r($flux['data'], true), 'campagnodon'._LOG_DEBUG);
	return $flux;
}

/**
 * Activer la souscription abonnee
 * @param $flux
 * @return mixed
 */
function campagnodon_bank_abos_activer_abonnement($flux) {
	$id_transaction = $flux['args']['id_transaction'];
	$transaction = _campagnodon_get_bank_transaction($id_transaction, __FUNCTION__);
	if (!$transaction) { return $flux; }

	spip_log(__FUNCTION__.': campagnodon doit gérer', 'campagnodon'._LOG_DEBUG);

	// Arguments fournis dans $flux:
	// 'id_transaction' => $id_transaction,
	// 'abo_uid' => $abo_uid,
	// 'mode_paiement' => $mode_paiement,
	// 'validite' => $validite,
	// 'id_auteur' => $id_auteur,
	// Doit retourner: id_abonnement
	// TODO.
	spip_log('NOT IMPLEMENTED YET '.__FUNCTION__, 'campagnodon'._LOG_ERREUR);
}


/**
 * preparer_echeance: on doit créer la transaction Campagnodon qui va accueillir une nouvelle mensualité.
 *
 * @param array
 * @return array
 */
function campagnodon_bank_abos_preparer_echeance($flux) {
	$id_transaction = $flux['args']['id_transaction'];
	$transaction = _campagnodon_get_bank_transaction($id_transaction, __FUNCTION__);
	if (!$transaction) { return $flux; }

	spip_log(__FUNCTION__.': campagnodon doit gérer', 'campagnodon'._LOG_DEBUG);
	// TODO.
	// Dois retourner: id_transaction
	spip_log('NOT IMPLEMENTED YET '.__FUNCTION__, 'campagnodon'._LOG_ERREUR);
}


/**
 * renouveler_abonnement: Cette fonction est appelée lorsque le plugin est notifié d’un paiement récurrent réussi.
 *
 * @param array
 * @return array
 */
function campagnodon_bank_abos_renouveler_abonnement($flux) {
	$id_transaction = $flux['args']['id_transaction'];
	$transaction = _campagnodon_get_bank_transaction($id_transaction, __FUNCTION__);
	if (!$transaction) { return $flux; }

	spip_log(__FUNCTION__.': campagnodon doit gérer', 'campagnodon'._LOG_DEBUG);

	// TODO.
	// Arguments recu dans $flux:
	// 'id_transaction' => $id_transaction,
	// 'abo_uid' => $abo_uid,
	// 'mode_paiement' => $mode_paiement,
	// 'validite' => $validite,
	// Dois retourner: id_abonnement
	spip_log('NOT IMPLEMENTED YET '.__FUNCTION__, 'campagnodon'._LOG_ERREUR);
}

/**
 * Prendre en charge la resiliation demandee par le client
 *
 * @param array $flux
 * @return array
 */
function campagnodon_bank_abos_resilier($flux) {
	$id_transaction = $flux['args']['id_transaction'];
	$transaction = _campagnodon_get_bank_transaction($id_transaction, __FUNCTION__);
	if (!$transaction) { return $flux; }

	spip_log(__FUNCTION__.': campagnodon doit gérer', 'campagnodon'._LOG_DEBUG);

	// Arguments recu dans $flux:
	// $args = array(
	// 	'id' => $id,
	// 	'message' => $options['message'],
	// 	'notify_bank' => $options['notify_bank'],
	// 	'erreur' => $options['erreur'],
	// );
	// $now = date('Y-m-d H:i:s');
	// if ($options['immediat']){
	// 	$args['statut'] = 'resilie';
	// 	$args['date_fin'] = $now;
	// 	$args['date_echeance'] = $now;
	// } else {
	// 	$args['date_fin'] = "date_echeance";
	// }

	// Dois retourner: $ok.
	// TODO: on doit probablement appeler abos_resilier_notify_bank le cas échéant.
	spip_log('NOT IMPLEMENTED YET '.__FUNCTION__, 'campagnodon'._LOG_ERREUR);
}
