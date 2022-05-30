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

function action_campagnodon_synchroniser_dist(){
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$id_campagnodon_transaction = $securiser_action();
	if (!preg_match("/^\d+$/", $id_campagnodon_transaction)) {
		spip_log('Argument id_campagnodon_transaction invalide: "'.$id_campagnodon_transaction.'"', 'campagnodon'._LOG_ERREUR);
		return;
	}
	if (!autoriser('campagnodon_synchroniser', 'campagnodon_transactions')) {
		spip_log('Utilisateur non autorisé à synchroniser.', 'campagnodon'._LOG_ERREUR);
		return;
	}

	spip_log('On doit planifier une synchronisation pour la transaction Campagnodon: "'.$id_campagnodon_transaction.'"', 'campagnodon'._LOG_DEBUG);
	include_spip('inc/campagnodon.utils');
	campagnodon_queue_synchronisation($id_campagnodon_transaction);
}
