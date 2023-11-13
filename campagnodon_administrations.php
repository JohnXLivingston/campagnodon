<?php

/**
 * Migration du plugin
 *
 * @plugin     Campagnodon
 * @copyright  2022
 * @author     John Livingston
 * @licence    AGPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function campagnodon_upgrade($nom_meta_base_version, $version_cible) {
  $maj = [];

	$maj['create'] = [
		['maj_tables',	['spip_campagnodon_transactions', 'spip_campagnodon_campagnes']]
	];

	$maj['2.0.0'] = [
		array(
			'sql_alter',
			'TABLE spip_campagnodon_transactions '
			. ' CHANGE type_transaction type_transaction varchar(30) NOT NULL DEFAULT \'don\''
		)
	];

  include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function campagnodon_vider_tables($nom_meta_base_version) {

	sql_drop_table('spip_campagnodon_transactions');
	sql_drop_table('spip_campagnodon_campagnes');

	effacer_meta($nom_meta_base_version);
}
