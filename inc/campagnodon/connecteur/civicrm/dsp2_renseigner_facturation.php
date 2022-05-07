<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Remonte les infos nécessaires pour DSP2.
 * @param $mode_options
 * @param $id_contact_distant
 * @param $flux
 */
function inc_campagnodon_connecteur_civicrm_dsp2_renseigner_facturation_dist($mode_options, $id_contact_distant, &$flux) {
  if (empty($id_contact_distant)) {
    return $flux;
  }

  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new civicrm_api3($mode_options['api_options']);
  $result = $civi_api->Contact->get([
    'contact_id' => $id_contact_distant
  ]);
  if (!$result) {
    spip_log("Erreur CiviCRM->Contact->get: " . $civi_api->errorMsg(), "campagnodon"._LOG_ERREUR);
  } else {
    foreach ($civi_api->values as $civi_contact) {
      spip_log('inc_campagnodon_connecteur_civicrm_dsp2_renseigner_facturation_dist: J\'ai bien trouvé les informations sur CiviCRM.', 'campagnodon'._LOG_DEBUG);
      $flux['data']['nom'] = $civi_contact->last_name;
      $flux['data']['prenom'] = $civi_contact->first_name;
      $flux['data']['email'] = $civi_contact->email;
      $flux['data']['adresse'] = $civi_contact->street_address;
      $flux['data']['code_postal'] = $civi_contact->postal_code;
      $flux['data']['ville'] = $civi_contact->city;
      $flux['data']['pays'] = $civi_contact->country;
      // spip_log('Réponse Civi: '.var_export($civi_contact, true), 'campagnodon'._LOG_DEBUG);
      // spip_log('Résultat: '.json_encode($flux['data']), 'campagnodon'._LOG_DEBUG);
      return $flux;
    }
  }
}
