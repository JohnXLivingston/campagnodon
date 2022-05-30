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


// pipeline de chargement
function campagnodon_autoriser($flux){
	return $flux;
}

function autoriser_campagnodon_synchroniser_dist($faire, $type, $id, $qui, $opt) {
  return autoriser('webmestre');
}
