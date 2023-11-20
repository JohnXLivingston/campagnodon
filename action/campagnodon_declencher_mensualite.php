<?php

/**
 * @plugin     Campagnodon
 * @copyright  2022
 * @author     John Livingston
 * @licence    AGPL-v3
 */


if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_campagnodon_declencher_mensualite_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_campagnodon_transaction = $securiser_action();
	if (!preg_match('/^\d+$/', $id_campagnodon_transaction)) {
		spip_log(__FUNCTION__.' Argument id_campagnodon_transaction invalide: "'.$id_campagnodon_transaction.'"', 'campagnodon'._LOG_ERREUR);
		return;
	}
	if (!autoriser('declencher_mensualite', 'campagnodon_transactions', $id_campagnodon_transaction)) {
		spip_log(__FUNCTION__.' Utilisateur non autorisé à synchroniser la transaction campagnodon '.$id_campagnodon_transaction.'.', 'campagnodon'._LOG_ERREUR);
		return;
	}

	spip_log(__FUNCTION__.' On doit planifier une synchronisation pour la transaction Campagnodon: "'.$id_campagnodon_transaction.'"', 'campagnodon'._LOG_DEBUG);
	$campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
	if (!$campagnodon_transaction) {
		spip_log(__FUNCTION__.' Impossible de remonter la transaction campagnodon'.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		return;
	}

	$id_transaction = $campagnodon_transaction['id_transaction'];
	if (!$id_transaction) {
		spip_log(__FUNCTION__.' la transaction campagnodon '.$id_campagnodon_transaction.' n\'a pas d\'id de transaction spip bank ', 'campagnodon'._LOG_ERREUR);
		return;
	}
	$abo_uid = sql_getfetsel('abo_uid', 'spip_transactions', 'id_transaction=' . intval($id_transaction));
	if (!$abo_uid) {
		spip_log(__FUNCTION__.' Impossible de remonter un abo_uid de spip_bank depuis la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		return;
	}

	$preparer_echeance = charger_fonction('preparer_echeance', 'abos', true);
	if (!$preparer_echeance) {
		spip_log(__FUNCTION__.' Impossible de trouver la fonction abos_preparer_echeance.', 'campagnodon'._LOG_ERREUR);
		return;
	}
	$id_transaction_enfant = $preparer_echeance('uid:' . $abo_uid);
	if (!$id_transaction_enfant) {
		spip_log(__FUNCTION__.' Echec de preparer_echeance pour la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		return;
	}

	// FIXME: le code ci-dessus ne suffit pas à déclencher tous les pipelines. Le code de SPIP Bank est complexe. Il faudrait trouver un moyen simble de le simuler.
	// FIXME: le code ci dessous n'a pas l'air de fonctionner

	// // On marque la transaction enfant comme réglée.
	// spip_log(__FUNCTION__.' On marque la transaction enfant '.$id_transaction_enfant.' comme réglée.', 'campagnodon'._LOG_DEBUG);
	// $regler_transaction = charger_fonction('regler_transaction', 'bank');
	// $regler_transaction($id_transaction_enfant);

	// // On récupère le mode de transaction sur la transaction parent:
	// spip_log(__FUNCTION__.' Il faut maintenant appeler abos_renouveler_abonnement pour la transaction enfant '.$id_transaction_enfant, 'campagnodon'._LOG_DEBUG);
	// $transaction_mode = sql_getfetsel('mode', 'spip_transactions', 'id_transaction=' . intval($id_transaction));
	// // Pour la date de fin... on prend demain (pourquoi pas ?)
	// $date_fin = new DateTime('tomorrow');
	// $date_fin = $date_fin->format('Y-m-d H:i:s');
	// $renouveler_abonnement = charger_fonction('renouveler_abonnement', 'abos', true);
	// if (!$renouveler_abonnement) {
	//   spip_log(__FUNCTION__.' Impossible de trouver la fonction abos_renouveler_abonnement.', 'campagnodon'._LOG_ERREUR);
	//   return;
	// }
	// $renouveler_abonnement($id_transaction_enfant, $abo_uid, $transaction_mode, $date_fin);
}
