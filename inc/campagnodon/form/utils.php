
<?php
/**
 * Dans ce fichier, quelques fonctions utilitaires pour le formulaire.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Cette fonction retourne la campagne si elle est bien valide.
 */
function form_utils_get_campagne_ouverte($id_campagne) {
	$where = 'id_campagnodon_campagne='.sql_quote(intval($id_campagne));
	$where.= " AND statut='publie'";
	return sql_fetsel('*', 'spip_campagnodon_campagnes', $where);
}


function form_utils_traduit_adhesion_type($mode_options, $type) {
	if (
		is_array($mode_options)
		&& array_key_exists('adhesion_type', $mode_options)
		&& is_array($mode_options['adhesion_type'])
		&& array_key_exists($type, $mode_options['adhesion_type'])
	) {
		return $mode_options['adhesion_type'][$type];
	}
	return $type;
}
