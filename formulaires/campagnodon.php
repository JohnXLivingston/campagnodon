<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
* Declarer les champs postes et y integrer les valeurs par defaut
*/
function formulaires_campagnodon_charger_dist($type, $id_campagne=NULL) {
  if ($type !== 'don') {
    spip_log("Type de Campagnodon inconnu: ".$type, "campagnodon"._LOG_ERREUR);
    return false;
  }
  $title = 'Je passe à l’Attac !';
  
  $amounts = [
    '13' => '13 € (moins de 450 € de revenus mensuels)',
    '21' => '21 € (entre 450 € et 900 € de revenus mensuels)',
    '35' => '35 € (entre 900 € et 1200 € de revenus mensuels)',
    '48' => '48 € (entre 1200 € et 1600 € de revenus mensuels)',
    '65' => '65 € (entre 1600 € et 2300 € de revenus mensuels)',
    '84' => '84 € (entre 2300 € et 3000 € de revenus mensuels)',
    '120' => '120 € (entre 3000 € et 4000 € de revenus mensuels)',
    '160' => '160 € (plus de 4000 € de revenus mensuels)',
  ];
  
  $values = [
    /* Éléments statiques */
    'form_title' => $title,
    'amounts' => $amounts,
    'amount' => '',
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'address' => '',
    'postal_code' => '',
    'city' => '',
    // 'country' => 'FR', FIXME: only France?
  ];
  
  return $values;
}

function formulaires_campagnodon_verifier_dist($type, $id_campagne=NULL) {
  $erreurs = [];
  
  $obligatoires = [];
  
  // $obligatoires = ['email', 'amount', 'first_name', 'last_name', 'address', 'postal_code', 'city'];
  $obligatoires = ['email', 'amount', 'first_name', 'last_name'];
  
  foreach($obligatoires as $obligatoire) {
    if(!_request($obligatoire)) {
      $erreurs[$obligatoire] = _T('info_obligatoire');
    }
  }
  
  return $erreurs;
}

function formulaires_campagnodon_traiter_dist($type, $id_campagne=NULL) {
  spip_log("traiter_dist" . $type . ":". $id_campagne);
  if (_CAMPAGNODON_MODE !== 'civicrm') {
    spip_log("Campagnodon Non configuré, constante _CAMPAGNODON_MODE manquante.", "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:erreur_sauvegarde")];
  }
  if (!defined('_CAMPAGNODON_CIVICRM_API_OPTIONS')) {
    spip_log("CiviCRM Non configuré, constante _CAMPAGNODON_CIVICRM_API_OPTIONS manquante.", "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:erreur_sauvegarde")];
  }

  $tracking_id = uniqid('', true);
  $inserer_transaction = charger_fonction('inserer_transaction', 'bank');
  $options = [
    'auteur' => _request('email'),
    'parrain' => 'campagnodon',
    'tracking_id' => $tracking_id,
    'force' => false,
  ];
  if (!(
    $id_transaction = $inserer_transaction(_request('amount'), $options)
    and $hash = sql_getfetsel('transaction_hash', 'spip_transactions', 'id_transaction=' . intval($id_transaction))
  )) {
    spip_log("Erreur à la création de la transaction ".$tracking_id, "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:erreur_sauvegarde")];
  }

  include_spip('inc/civicrm/class.api');
  $civi_api = new civicrm_api3(_CAMPAGNODON_CIVICRM_API_OPTIONS);

  $result = $civi_api->Attac->create_member([
    'first_name' => _request('first_name'),
    'last_name' => _request('last_name'),
    'email' => _request('email'),
    'address' => _request('address'),
    'postal_code' => _request('postal_code'),
    'city' => _request('city'),
    'amount' => _request('amount'),
    'payment_method' => 'transfer' // FIXME: use the correct value
  ]);

  spip_log('Résultat CiviCRM: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

  if (!$result) {
    spip_log("Erreur CiviCRM " . $civi_api->errorMsg(), "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:erreur_sauvegarde")];
  }

  return [
    'redirect' => generer_url_public('payer-acte', "id_transaction=$id_transaction&transaction_hash=$hash", false, false),
    'editable' => false,
  ];
}
