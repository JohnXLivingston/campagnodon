<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Converti le type de contribution.
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $idx
 * La référence externe de la transaction
 * @param $nouveau_type
 * Le nouveau type.
 */
function inc_campagnodon_connecteur_civicrm_convertir_dist($mode_options, $idx, $nouveau_type_distant) {
  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new campagnodon_civicrm_api3($mode_options['api_options']);

  $result = $civi_api->Campagnodon->convert(array(
    'campagnodon_version' => 1,
    'transaction_idx' => $idx,
    'operation_type' => $nouveau_type_distant,
  ));
  if (!$result) {
    throw new Exception("Erreur CiviCRM " . $civi_api->errorMsg());
  }

  $civicrm_result = $civi_api->values;
  $line = array_pop($civicrm_result);
  if (!$line) {
    throw new Exception("Erreur CiviCRM, je ne trouve pas de ligne dans le résultat.");
  }
  return array(
    'operation_type' => $line->operation_type
  );
}
