<?php

/**
 * Définit les autorisations du plugin Campagnodon
 *
 * @plugin     Campagnodon
 * @copyright  2022
 * @author     John Livingston
 * @licence    AGPL-v3
 * @package    SPIP\Campagnodon\Autorisations
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Pipeline de chargement.
 * Cette fonction doit être présente pour que le pipeline soit pris en compte.
 * Elle n'a rien de particulier à faire.
 */
function campagnodon_autoriser($flux){
	return $flux;
}

// FIXME: je n'arrive pas à faire fonctionner ces fonctions pour les droits.
// function autoriser_campagnodoncampagne_dist($faire, $type, $id, $qui, $opt) {
// 	return false;
// }

// function autoriser_campagnodontransaction_dist($faire, $type, $id, $qui, $opt) {
// 	return false;
// }

function autoriser_campagnodon_synchroniser_dist($faire, $type, $id, $qui, $opt) {
  return autoriser('webmestre');
}

function autoriser_campagnodon_convertir_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('webmestre');
}

function autoriser_campagnodon_declencher_mensualite_dist($faire, $type, $id, $qui, $opt) {
	if (!defined('_CAMPAGNODON_DON_RECURRENT_DEBUG') || !_CAMPAGNODON_DON_RECURRENT_DEBUG) {
		return false;
	}
	if ($id) {
		$campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($id));
		if (!$campagnodon_transaction) {
			spip_log(__FUNCTION__.' Impossible de remonter la transaction campagnodon='.$id, 'campagnodon'._LOG_ERREUR);
			return false;
		}
		if ($campagnodon_transaction['type_transaction'] !== 'don_mensuel') {
			return false;
		}
		if ($campagnodon_transaction['id_campagnodon_transaction_parent']) {
			return false;
		}
		if ($campagnodon_transaction['statut_recurrence'] !== 'encours') {
			return false;
		}
	}
	return autoriser('webmestre');
}
