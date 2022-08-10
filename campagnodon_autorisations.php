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
