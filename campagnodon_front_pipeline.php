<?php

/**
 * Divers pipelines définis par le plugin Campagnodon pour le front-end
 * 
 * @plugin Campagnodon
 * @copyright 2022
 * @author John Livingston
 * @licence AGPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function campagnodon_jquery_plugins($scripts) {
  $scripts[] = 'javascript/campagnodon.js';
  return $scripts;
}