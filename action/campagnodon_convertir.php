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

function action_campagnodon_convertir_dist(){
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	if (!preg_match("/^(\d+)-(\w+)$/", $arg, $matches)) {
		spip_log('Argument invalide pour l\'action convertir: "'.$arg.'"', 'campagnodon'._LOG_ERREUR);
		return;
	}
	$id_campagnodon_transaction = $matches[1];
	$nouveau_type = $matches[2];
	if (!autoriser('campagnodon_convertir', 'campagnodon_transactions')) {
		spip_log('Utilisateur non autorisé à convertir.', 'campagnodon'._LOG_ERREUR);
		return;
	}

	include_spip('inc/campagnodon.utils');
	campagnodon_converti_transaction_en($id_campagnodon_transaction, $nouveau_type);
}
