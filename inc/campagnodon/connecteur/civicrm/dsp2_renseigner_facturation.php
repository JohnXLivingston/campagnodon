<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Remonte les infos nécessaires pour DSP2.
 * @param $mode_options
 * @param $transaction_distant
 */
function inc_campagnodon_connecteur_civicrm_dsp2_renseigner_facturation_dist($mode_options, $transaction_distant) {
  if (empty($transaction_distant)) {
    return false;
  }

  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new civicrm_api3($mode_options['api_options']);
  $result = $civi_api->Campagnodon->Dsp2info([
    'transaction_idx' => $transaction_distant
  ]);
  if (!$result) {
    spip_log("Erreur CiviCRM->Campagnodon->Dsp2info: " . $civi_api->errorMsg(), "campagnodon"._LOG_ERREUR);
  } else {
    foreach ($civi_api->values as $civi_infos) {
      spip_log('inc_campagnodon_connecteur_civicrm_dsp2_renseigner_facturation_dist: J\'ai bien trouvé les informations sur CiviCRM.', 'campagnodon'._LOG_DEBUG);
      $flux['data']['nom'] = $civi_infos->last_name;
      $flux['data']['prenom'] = $civi_infos->first_name;
      $flux['data']['email'] = $civi_infos->email;
      $flux['data']['adresse'] = $civi_infos->street_address;
      $flux['data']['code_postal'] = $civi_infos->postal_code;
      $flux['data']['ville'] = $civi_infos->city;
      $flux['data']['pays'] = $civi_infos->country;
      spip_log('Réponse Civi: '.var_export($civi_infos, true), 'campagnodon'._LOG_DEBUG);
      spip_log('Résultat: '.json_encode($flux['data']), 'campagnodon'._LOG_DEBUG);
      return $flux;
    }
  }
}
