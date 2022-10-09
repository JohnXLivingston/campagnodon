<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Cette fonction sert à migrer une contribution déjà existante.
 * C'est utilisé chez Attac France pour migrer les contributions
 * qui ont été importées dans CiviCRM de l'ancien plugin Souscription.
 * 
 * C'est hautement expérimental.
 * 
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $params
 *  Les données à envoyer.
 *  TODO: documenter le format de ces données.
 */
function inc_campagnodon_connecteur_civicrm_migrer_contribution_dist($mode_options, $params) {
  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new campagnodon_civicrm_api3($mode_options['api_options']);
  $result = $civi_api->Campagnodon->migratecontribution($params);

  // spip_log('Résultat CiviCRM recurrence: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

  if (!$result) {
    throw new Exception("Erreur CiviCRM " . $civi_api->errorMsg());
  }

  return true;
}
