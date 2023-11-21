<?php
/*
 * Auteurs :
 * John Livingston
 * (c) 2022 - AGPL-v3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function campagnodon_campagne_souscriptions_perso_json($origine) {
	include_spip('inc/campagnodon.utils');
	$mode_options = campagnodon_mode_options($origine);
	if (!is_array($mode_options) || !array_key_exists('souscriptions_optionnelles', $mode_options)) {
		return array();
	}
	$result = array();
	foreach (['don', 'adhesion'] as $type) {
		$r = array();
		foreach ($mode_options['souscriptions_optionnelles'] as $k => $so) {
			if (!array_key_exists('pour', $so) || empty($so['pour'])) {
				$r[] = $k;
			} elseif (is_array($so['pour'])) {
				if (
					false !== array_search($type, $so['pour'], true)
					|| false !== array_search($type.'?', $so['pour'], true)
				) {
					$r[] = $k;
				}
			}
		}
		$result[$type] = $r;
	}

	// Pour don+adhesion, on merge les 2 listes en enlevant les doublons.
	$result['don+adhesion'] = array();
	foreach (['don', 'adhesion'] as $type) {
		foreach ($result[$type] as $k) {
			if (!in_array($k, $result['don+adhesion'])) {
				$result['don+adhesion'][] = $k;
			}
		}
	}
	//  array_values(array_unique(array_merge($result['don'], $result['adhesion'])));

	return json_encode($result);
}

function campagnodon_campagne_montants_par_defaut() {
	include_spip('inc/campagnodon.utils');
	$r = [];
	foreach (['don', 'adhesion'] as $type) {
		$r[$type] = implode(',', campagnodon_montants_par_defaut($type, 'unique'));
	}
	if (campagnodon_campagne_don_recurrent()) {
		$r['don_mensuel'] = implode(',', campagnodon_montants_par_defaut('don', 'mensuel'));
		// FIXME: remplacer par un équivalent à campagnodon_don_recurrent_active() pour les adhésions ?
		$r['adhesion_annuel'] = implode(',', campagnodon_montants_par_defaut('adhesion', 'annuel'));
	}
	return json_encode($r);
}

function campagnodon_campagne_don_recurrent() {
	include_spip('inc/campagnodon.utils');
	return campagnodon_don_recurrent_active();
}
