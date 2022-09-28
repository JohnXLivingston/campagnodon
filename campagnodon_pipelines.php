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
		spip_log(__FUNCTION__.': id_transaction est falsey. Caller='.$caller, 'campagnodon'._LOG_ERREUR);
		return null;
	}
	$transaction = sql_fetsel('*', 'spip_transactions', 'id_transaction=' . intval($id_transaction));
	if (!$transaction) {
		spip_log(__FUNCTION__.': Transaction introuvable pour id_transaction='.$id_transaction.'. Caller='.$caller, "campagnodon"._LOG_ERREUR);
		return null;
	}
	if ($transaction['parrain'] !== 'campagnodon') {
		// Ça ne concerne pas notre plugin.
		spip_log(__FUNCTION__.': programmer_sync_bank_result: cette ligne n\'a pas campagnodon comme parrain. Caller='.$caller, 'campagnodon'._LOG_DEBUG);
		return null;
	}
	return $transaction;
}

/**
 * Cette fonction retourne la transaction SPIP Bank et la transaction Campagnodon à l'origine d'un paiement récurrent,
 * sous réserve que le parrain soit bien Campagnodon.
 * @param $abo_uid
 * @param $caller Nom de la fonction appellante. Sert pour les logs générés.
 * @return $transaction
 */
function _campagnodon_get_abo_transactions($abo_uid, $caller) {
	if (!$abo_uid) {
		spip_log(__FUNCTION__.': $abo_uid est falsey. Caller='.$caller, 'campagnodon'._LOG_ERREUR);
		return [null, null];
	}
	if (strncmp($abo_uid,"uid:",4)!==0){
		spip_log(__FUNCTION__.': $abo_uid ne commence pas par uid:, je ne sais pas traiter. abo_uid="'.$abo_uid.'"Caller='.$caller, 'campagnodon'._LOG_ERREUR);
		return [null, null];
	}
	$abo_uid = substr($abo_uid,4);

	// On va chercher une transaction liée à cet abonnement.
	// Attention, les transactions correspondantes aux mensualités aussi peuvent avoir l'abo_uid.
	// Je n'ai aucune garantie sur celle qui sera retournée par la requête ci-dessous.
	// Mais elle me permettra de remonter jusqu'à la transaction initiale (et jusqu'à la transaction campagnodon initiale).
	// NB: pour maximiser les chances d'arriver directement sur la bonne, on va trier par id_transaction.
	$transaction_quelconque = sql_fetsel(
		'*',
		'spip_transactions',
		'abo_uid=' . sql_quote($abo_uid),
		'',
		'id_transaction ASC'
	);
	if (!$transaction_quelconque) {
		spip_log(__FUNCTION__.': Transaction introuvable pour abo_uid='.$abo_uid.'. Caller='.$caller, "campagnodon"._LOG_ERREUR);
		return [null, null];
	}
	if ($transaction_quelconque['parrain'] !== 'campagnodon') {
		// Ça ne concerne pas notre plugin.
		spip_log(__FUNCTION__.': cette ligne n\'a pas campagnodon comme parrain. Caller='.$caller, 'campagnodon'._LOG_DEBUG);
		return [null, null];
	}

	$campagnodon_transaction_quelconque = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_transaction=' . intval($transaction_quelconque['id_transaction']));
	if (!$campagnodon_transaction_quelconque) {
		spip_log(__FUNCTION__.': Transaction Campagnodon introuvable pour id_transaction='.$transaction_parent['id_transaction'].'. Caller='.$caller, 'campagnodon'._LOG_ERREUR);
		return [null, null];
	}

	while ($campagnodon_transaction_quelconque && ($campagnodon_transaction_quelconque['id_campagnodon_transaction_parent'] !== null)) {
		$id = $campagnodon_transaction_quelconque['id_campagnodon_transaction_parent'];
		$campagnodon_transaction_quelconque = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction=' . intval($id));
		if (!$campagnodon_transaction_quelconque) {
			spip_log(__FUNCTION__.': Transaction Campagnodon introuvable id_campagnodon_transaction='.$id, 'campagnodon'._LOG_ERREUR);
			return [null, null];
		}
	}

	$campagnodon_transaction = $campagnodon_transaction_quelconque;
	if ($transaction_quelconque['tracking_id'] == $campagnodon_transaction['id_campagnodon_transaction']) {
		// on avait déjà la bonne !
		$transaction = $transaction_quelconque;
	} else {
		$transaction = sql_fetsel(
			'*',
			'spip_transactions',
			['parrain=' . sql_quote('campagnodon'), 'tracking_id='.sql_quote($campagnodon_transaction['id_campagnodon_transaction'])],
			'',
			'id_transaction ASC'
		);
		if (!$transaction) {
			spip_log(__FUNCTION__.': Transaction introuvable pour parrain=campagnodon & tracking_id='.$campagnodon_transaction['id_campagnodon_transaction'].'. Caller='.$caller, "campagnodon"._LOG_ERREUR);
			return [null, null];
		}
	}


	return [$transaction, $campagnodon_transaction];
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
	// NB: ne pas tester si $flux['data'] est rempli ici
	// (contrairement à d'autres pipelines où on considère que si qqun y a touché, c'est que je n'ai rien à y faire),
	// c'est pré-rempli par SPIP Bank.

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
	if (defined('_CAMPAGNODON_DON_RECURRENT_JOUR') && is_numeric(_CAMPAGNODON_DON_RECURRENT_JOUR)) {
		$day = intval(_CAMPAGNODON_DON_RECURRENT_JOUR);
		$date_start = new DateTime('now');
		$date_start->setDate($date_start->format('Y'), $date_start->format('m'), _CAMPAGNODON_DON_RECURRENT_JOUR);
		$date_start->modify('+1 month');
		$date_start = $date_start->format('Y-m-d');
		$flux['data']['date_start'] = $date_start;
	}
	spip_log('campagnodon_bank_abos_decrire_echeance: voici les infos remontées: '.print_r($flux['data'], true), 'campagnodon'._LOG_DEBUG);
	return $flux;
}

/**
 * Activer la souscription abonnee
 * Attention, cette fonction peut être appelée 2 fois en cas d'encaissement différé,
 * une fois «en attente de paiement», une fois «payée».
 * @param $flux
 * @return mixed
 */
function campagnodon_bank_abos_activer_abonnement($flux) {
	if ($flux['data']) {
		spip_log(__FUNCTION__.': il y a déjà quelque chose dans data, un autre plugin a dû répondre.', 'campagnodon'._LOG_DEBUG);
		return $flux;
	}
	$id_transaction = $flux['args']['id_transaction'];
	$transaction = _campagnodon_get_bank_transaction($id_transaction, __FUNCTION__);
	if (!$transaction) { return $flux; }

	$id_campagnodon_transaction = $transaction['tracking_id'];
	spip_log(__FUNCTION__.': campagnodon doit gérer la transaction='.$id_transaction.', transaction campagnodon=' . $id_campagnodon_transaction, 'campagnodon'._LOG_DEBUG);

	if (strncmp($transaction['statut'], 'ok', 2) == 0) {
		spip_log(__FUNCTION__.': la transaction campagnodon '.$id_campagnodon_transaction.' est en statut ok, j\'active', 'campagnodon'._LOG_DEBUG);
		$statut_recurrence = 'encours';
	} else {
		spip_log(__FUNCTION__.': la transaction campagnodon '.$id_campagnodon_transaction.' n\'est pas encore en statut ok, je n\'active pas encore', 'campagnodon'._LOG_DEBUG);
		$statut_recurrence = 'attente';
	}

	// Arguments fournis dans $flux:
	// 'id_transaction' => $id_transaction,
	// 'abo_uid' => $abo_uid,
	// 'mode_paiement' => $mode_paiement,
	// 'validite' => $validite,
	// 'id_auteur' => $id_auteur,
	// Doit retourner: id_abonnement (qui n'a pas l'air utilisé par SPIP Bank)

	spip_log(__FUNCTION__.': Je passe le statut de récurrence à '.$statut_recurrence.' pour la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon'._LOG_DEBUG);
	if (false === sql_updateq(
		'spip_campagnodon_transactions',
		[
			'statut_recurrence' => $statut_recurrence
		],
		'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
	)) {
		spip_log(__FUNCTION__.': Erreur à la modification de la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		return $flux;
	}

	include_spip('inc/campagnodon.utils');
	campagnodon_queue_synchronisation($id_campagnodon_transaction);

	$flux['data'] = $id_campagnodon_transaction; // SPIP Bank ne semble pas utiliser la valeur de retour, mais on va quand même mettre quelquechose.
	return $flux;
}


/**
 * preparer_echeance: on doit créer la transaction Campagnodon qui va accueillir une nouvelle mensualité.
 * À noter qu'ici le paiement peut être réussi ou raté.
 *
 * @param array
 * @return bool|int id_transaction L'id de la transaction spip bank créée.
 */
function campagnodon_bank_abos_preparer_echeance($flux) {
	if ($flux['data']) {
		spip_log(__FUNCTION__.': il y a déjà quelque chose dans data, un autre plugin a dû répondre.', 'campagnodon'._LOG_DEBUG);
		return $flux;
	}

	// Note: ici $flux['args']['id'] est de la forme uid:xxx !
	$abo_uid = $flux['args']['id'];

	// On doit commencer par chercher la transaction spip_bank originelle, puis on regarde si ça concerne campagnodon.
	list ($transaction_parent, $campagnodon_transaction_parent) = _campagnodon_get_abo_transactions($abo_uid, __FUNCTION__);
	if (!$transaction_parent || !$campagnodon_transaction_parent) {
		spip_log(__FUNCTION__.': il semblerait que je n\'ai pas à traiter abo_uid='.$abo_uid, 'campagnodon'._LOG_DEBUG);
		return $flux;
	}

	spip_log(__FUNCTION__.': campagnodon doit gérer l\'abonnement='.$abo_uid, 'campagnodon'._LOG_DEBUG);

	$id_campagnodon_transaction_parent = $campagnodon_transaction_parent['id_campagnodon_transaction'];

	$id_campagnodon_transaction = sql_insertq('spip_campagnodon_transactions', [
		'id_campagnodon_campagne' => $campagnodon_transaction_parent['id_campagnodon_campagne'],
		'type_transaction' => 'don_mensuel_echeance',
		'mode' => $campagnodon_transaction_parent['mode'],
		'id_campagnodon_transaction_parent' => $id_campagnodon_transaction_parent
	]);
	if (!$id_campagnodon_transaction) {
		spip_log(__FUNCTION__.': Impossible de créer la transaction campagnodon', 'campagnodon'._LOG_ERREUR);
		return $flux;
	}

	$inserer_transaction = charger_fonction('inserer_transaction', 'bank');
	$transaction_options = [
		// 'auteur' => _request('email'), // FIXME: peut-on se passer de cette info ?
		'parrain' => 'campagnodon',
		'tracking_id' => $id_campagnodon_transaction,
		'force' => true
		// FIXME: faut-il ajouter ceci ? 'abo_uid' => $abo_uid
	];
	$montant_total = $transaction_parent['montant']; // Le montant des échéances est le même que la transaction initiale.
  $id_transaction = $inserer_transaction($montant_total, $transaction_options);
	if (!$id_transaction) {
		spip_log(__FUNCTION__.': Impossible de créer la transaction SPIP Bank', 'campagnodon'._LOG_ERREUR);
		return $flux;
	}

	include_spip('inc/campagnodon.utils');
	$mode_options = campagnodon_mode_options($campagnodon_transaction_parent['mode']);
	$transaction_idx_distant = get_transaction_idx_distant($mode_options, $id_campagnodon_transaction, $id_campagnodon_transaction_parent);
	if (false === sql_updateq(
		'spip_campagnodon_transactions',
		[
			'id_transaction' => $id_transaction,
			'transaction_distant' => $transaction_idx_distant
		],
		'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
	)) {
		spip_log(__FUNCTION__.': Erreur à la modification de la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		return $flux;
	}

	// Note: on ne créé pas tout de suite la transaction sur le système distant.
	// Car en panne de celui-ci, on pourrait rater des choses.
	// On va plutôt marquer la ligne à synchroniser, et c'est la synchro qui détectera qu'il faut créé l'échéance.
	include_spip('inc/campagnodon.utils');
	campagnodon_queue_synchronisation($id_campagnodon_transaction);

	$flux['data'] = $id_transaction;
	return $flux;
}


/**
 * renouveler_abonnement: Cette fonction est appelée lorsque le plugin est notifié d’un paiement récurrent réussi.
 *
 * @param array
 * @return array
 */
function campagnodon_bank_abos_renouveler_abonnement($flux) {
	if ($flux['data']) {
		spip_log(__FUNCTION__.': il y a déjà quelque chose dans data, un autre plugin a dû répondre.', 'campagnodon'._LOG_DEBUG);
		return $flux;
	}

	$id_transaction = $flux['args']['id_transaction'];
	$transaction = _campagnodon_get_bank_transaction($id_transaction, __FUNCTION__);
	if (!$transaction) { return $flux; }

	$id_campagnodon_transaction = $transaction['tracking_id'];
	spip_log(__FUNCTION__.': campagnodon doit gérer id_campagnodon_transaction='.$id_campagnodon_transaction, 'campagnodon'._LOG_DEBUG);

	// Arguments recu dans $flux:
	// 'id_transaction' => $id_transaction,
	// 'abo_uid' => $abo_uid,
	// 'mode_paiement' => $mode_paiement,
	// 'validite' => $validite,
	// Dois retourner: id_abonnement (qui n'a pas l'air utilisé par SPIP Bank)

	include_spip('inc/campagnodon.utils');
	campagnodon_queue_synchronisation($id_campagnodon_transaction);

	$flux['data'] = $id_campagnodon_transaction; // SPIP Bank ne semble pas utiliser la valeur de retour, mais on va quand même mettre quelquechose.
	return $flux;
}

/**
 * Prendre en charge la resiliation demandee par le client.
 * NB: la doc de SPIP Bank indique que ceci est appelé en cas de paiement récurrent échoué.
 *
 * @param array $flux
 * @return array
 */
function campagnodon_bank_abos_resilier($flux) {
	if ($flux['data']) {
		spip_log(__FUNCTION__.': il y a déjà quelque chose dans data, un autre plugin a dû répondre.', 'campagnodon'._LOG_DEBUG);
		return $flux;
	}

	// Ici pas de id_transaction, mais id (qui est de la forme uid:xxxxxxxxxx)
	$abo_uid = $flux['args']['id'];
	list ($transaction_parent, $campagnodon_transaction_parent) = _campagnodon_get_abo_transactions($abo_uid, __FUNCTION__);
	if (!$transaction_parent || !$campagnodon_transaction_parent) {
		spip_log(__FUNCTION__.': il semblerait que je n\'ai pas à traiter abo_uid='.$abo_uid, 'campagnodon'._LOG_DEBUG);
		return $flux;
	}

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

	// On doit appeler abos_resilier_notify_bank le cas échéant.
	// (ce code est inspiré de ce qu'on trouve dans le plugin Souscription.)
	if (
		$flux['args']['notify_bank']
		AND $mode_paiement = sql_getfetsel("mode","spip_transactions","abo_uid=".sql_quote($abo_uid,'','text'),"","id_transaction DESC")
	){
		$ok = abos_resilier_notify_bank($abo_uid,$mode_paiement);
		if (!$ok) {
			spip_log(__FUNCTION__.': Erreur à l appel de abos_resilier_notif_bank', 'campagnodon'._LOG_ERREUR);
			return $flux;
		}
	}

	$id_campagnodon_transaction_parent = $campagnodon_transaction_parent['id_campagnodon_transaction'];
	$statut_recurrence = 'termine';
	spip_log(__FUNCTION__.': Je passe le statut de récurrence à '.$statut_recurrence.' pour la transaction campagnodon '.$id_campagnodon_transaction_parent, 'campagnodon'._LOG_DEBUG);
	if (false === sql_updateq(
		'spip_campagnodon_transactions',
		[
			'statut_recurrence' => $statut_recurrence
		],
		'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction_parent)
	)) {
		spip_log(__FUNCTION__.': Erreur à la modification de la transaction campagnodon '.$id_campagnodon_transaction_parent, 'campagnodon'._LOG_ERREUR);
		return $flux;
	}

	include_spip('inc/campagnodon.utils');
	campagnodon_queue_synchronisation($id_campagnodon_transaction);

	$flux['data'] = true;
	return $flux;
}
