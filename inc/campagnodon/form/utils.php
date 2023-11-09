
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


/**
 * Retourne le montant saisi (en tenant comptes des montants libres le cas échéant).
 * Si le montant n'est pas libre, et n'est pas dans la liste configurée, retournen null.
 * @param $config_montants Correspond au retour de la fonction form_init_liste_montants_campagne
 */
function form_utils_read_montant($config_montants, $choix_type, $choix_recurrence) {
	$v_montant = _request('montant');
	if (empty($v_montant)) {
		return null;
	}

	// On doit vérifier que $v_montant existe dans $config_montants['propositions'],
	// pour cette combinaison type/recurrence.
	$pour_type = $choix_type;
	if (!empty($choix_recurrence) && $choix_recurrence !== 'unique') {
		$pour_type.= '_recurrent';
	}
	$trouve = false;
	foreach ($config_montants['propositions'] as $proposition) {
		if ($proposition['pour_type'] === $pour_type && ''.$proposition['valeur'] === $v_montant) {
			$trouve = true;
			break;
		}
	}
	if (!$trouve) {
		spip_log(
			'La valeur "'.$v_montant.'" ne fait pas parti de la liste de choix pour '.$pour_type,
			'campagnodon'._LOG_DEBUG
		);
		return null;
	}

	// On a bien saisi une valeur de la liste.
	// Si c'est 'libre', il faut lire le champs 'montant_libre'.
	if ($v_montant === 'libre') {
		$v_montant = trim(_request('montant_libre'));
	}

	return $v_montant;
}
