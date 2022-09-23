<?php

/**
 * @plugin     Campagnodon
 * @copyright  2022
 * @author     John Livingston
 * @licence    AGPL-v3
 */


if (!defined('_ECRIRE_INC_VERSION')){
	return;
}

function action_campagnodon_declencher_mensualite_dist(){
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_campagnodon_transaction = $securiser_action();
	if (!preg_match("/^\d+$/", $id_campagnodon_transaction)) {
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
  $id_transaction = $preparer_echeance("uid:" . $abo_uid);
  if (!$id_transaction) {
    spip_log(__FUNCTION__.' Echec de preparer_echeance pour la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
    return;
  }
}
