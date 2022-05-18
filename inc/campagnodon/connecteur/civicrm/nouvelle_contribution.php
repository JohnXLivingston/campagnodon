<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Créé la contribution en statut «en attente» dans le système distant.
 */
function inc_campagnodon_connecteur_civicrm_nouvelle_contribution_dist($mode_options, $params) {
  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new civicrm_api3($mode_options['api_options']);
  $result = $civi_api->Campagnodon->start($params);

  // spip_log('Résultat CiviCRM: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

  if (!$result) {
    throw new Exception("Erreur CiviCRM " . $civi_api->errorMsg());
  }

  // $civicrm_result = $civi_api->values;
  // if (empty($civicrm_result)) {
  //   $civicrm_result = [];
  // }
  // if (!empty($civicrm_result->donation)) {
  //   $update_campagnodon_transaction['id_don_distant'] = $civicrm_result->donation->id;
  //   civicrm_search_contact_id($civicrm_result->donation, $update_campagnodon_transaction);
  // }
  // if (!empty($civicrm_result->subscription)) {
  //   $update_campagnodon_transaction['id_adhesion_distant'] = $civicrm_result->subscription->id;
  //   civicrm_search_contact_id($civicrm_result->subscription, $update_campagnodon_transaction);
  // }

  return true;
}

// TODO: nettoyer ce code ci ce n'est plus nécessaire.
// /**
//  * Cette fonction cherche le contact_id dans un sous-objet CiviCRM.
//  */
// function civicrm_search_contact_id($obj, &$update_campagnodon_transaction) {
//   // spip_log(var_export($obj, true), "campagnodon"._LOG_DEBUG);
//   if (!empty($update_campagnodon_transaction['id_contact_distant'])) {
//     // On a déjà l'id.
//     return;
//   }
//   if (empty($obj)) {
//     return;
//   }
//   $id = strval($obj->id);
//   if (empty($id)) {
//     return;
//   }
//   if (empty($obj->values)) {
//     return;
//   }
//   if (empty($obj->values->$id)) {
//     return;
//   }
//   if (empty($obj->values->$id->contact_id)) {
//     return;
//   }
//   $update_campagnodon_transaction['id_contact_distant'] = $obj->values->$id->contact_id;
// }
