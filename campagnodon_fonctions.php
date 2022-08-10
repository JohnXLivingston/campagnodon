<?php
/*
 * Auteurs :
 * John Livingston
 * (c) 2022 - AGPL-v3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')){
	return;
}

/**
 * Retourne une liste de types de contributions vers lesquels on peut convertir la transaction.
 * @param $id_transaction
 * @return array
 */
function filtre_campagnodon_peut_convertir_en_dist($id_campagnodon_transaction) {
  include_spip('inc/campagnodon.utils');
  return campagnodon_peut_convertir_transaction_en($id_campagnodon_transaction);
}
