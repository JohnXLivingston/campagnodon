<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Cette fonction doit créer (le cas échéant) la transaction correspondante à une nouvelle échéance de don mensuel.
 * Retourne `true` en cas de succès.
 * NB: si la transaction a déjà été créé, il faut également retourner `true` (il pourrait y avoir des appels concurrents).
 * 
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $parent_idx
 * La référence externe de la transaction parent
 * @param $idx
 * La référence externe de la transaction
 * @param $params
 *  Les données à envoyer.
 *  TODO: documenter le format de ces données.
 */
function inc_campagnodon_connecteur_civicrm_garantir_echeance_existe_dist($mode_options, $parent_idx, $idx, $params) {
  $params = array_merge($params, [
    'transaction_idx' => $idx,
    'parent_transaction_idx' => $parent_idx,
  ]);

  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new campagnodon_civicrm_api3($mode_options['api_options']);
  $result = $civi_api->Campagnodon->recurrence($params);

  // spip_log('Résultat CiviCRM recurrence: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

  if (!$result) {
    throw new Exception("Erreur CiviCRM " . $civi_api->errorMsg());
  }

  return true;
}
