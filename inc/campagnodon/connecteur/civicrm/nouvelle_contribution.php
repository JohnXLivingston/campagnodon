<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Créé la contribution en statut «en attente» dans le système distant.
 */
function inc_campagnodon_connecteur_civicrm_nouvelle_contribution_dist($mode_options, $params) {
  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new campagnodon_civicrm_api3($mode_options['api_options']);
  $result = $civi_api->Campagnodon->start($params);

  // spip_log('Résultat CiviCRM: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

  if (!$result) {
    throw new Exception("Erreur CiviCRM " . $civi_api->errorMsg());
  }

  // $civicrm_result = $civi_api->values;
  // if (empty($civicrm_result)) {
  //   $civicrm_result = [];
  // }

  return true;
}
