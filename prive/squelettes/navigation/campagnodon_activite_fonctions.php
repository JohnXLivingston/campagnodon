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
 * Indique si on peut effectuer la migration en question.
 * 
 */
function filtre_campagnodon_peut_migration_souscription_don_recurrent_dist() {
  include_spip('action/campagnodon_migration');
  $migration_config = campagnodon_migration_config('souscription', 'don_recurrent');
  return !empty($migration_config);
}
