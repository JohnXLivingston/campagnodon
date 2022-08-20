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
      } else if (is_array($so['pour'])) {
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

  return json_encode($result);
}

function campagnodon_campagne_montants_par_defaut() {
  include_spip('inc/campagnodon.utils');
  $r = [];
  foreach (['don', 'adhesion'] as $type) {
    $r[$type] = implode(',', campagnodon_montants_par_defaut($type));
  }
  if (campagnodon_campagne_don_recurrent()) {
    $r['don_recurrent'] = implode(',', campagnodon_montants_par_defaut('don_recurrent'));
  }
  return json_encode($r);
}

function campagnodon_campagne_don_recurrent () {
  return defined('_CAMPAGNODON_DON_RECURRENT') && _CAMPAGNODON_DON_RECURRENT === true;
}
