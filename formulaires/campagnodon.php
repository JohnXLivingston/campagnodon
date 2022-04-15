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

  $campagne = get_campagne_ouverte($id_campagne);
  if (empty($campagne)) {
    spip_log("Campagne introuvable: ".$id_campagne, "campagnodon"._LOG_ERREUR);
    return false;
  }

  $title = $campagne['titre'];
  
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

  $campagne = get_campagne_ouverte($id_campagne);
  if (empty($campagne)) {
    $erreurs['message_erreur'] = _T('campagnodon:campagne_invalide');
  }
  
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

  $campagne = get_campagne_ouverte($id_campagne);
  if (empty($campagne)) {
    spip_log('Campagne invalide au moment de l\'enregistrement', "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:campagne_invalide")];
  }
  if ($campagne['origine'] !== 'civicrm') {
    spip_log('La campagne n\'a pas CiviCRM pour origine.', "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:campagne_invalide")];
  }

  $id_campagnodon_transaction = sql_insertq('spip_campagnodon_transactions', [
    'id_campagnodon_campagne' => $id_campagne,
    'type_distant' => 'civicrm'
  ]);
  if (!($id_campagnodon_transaction > 0)) {
    spip_log("Erreur à la création de la transaction campagnodon ", "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:erreur_sauvegarde")];
  }

  $inserer_transaction = charger_fonction('inserer_transaction', 'bank');
  $options = [
    // 'auteur' => _request('email'), // FIXME: peut-on se passer de cette info ?
    'parrain' => 'campagnodon',
    'tracking_id' => $id_campagnodon_transaction,
    'force' => true
  ];
  if (!(
    $id_transaction = $inserer_transaction(_request('amount'), $options)
    and $hash = sql_getfetsel('transaction_hash', 'spip_transactions', 'id_transaction=' . intval($id_transaction))
  )) {
    spip_log("Erreur à la création de la transaction ".$id_campagnodon_transaction, "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:erreur_sauvegarde")];
  }

  if (false === sql_updateq(
    'spip_campagnodon_transactions',
    [
      'id_transaction' => $id_transaction
    ],
    'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
  )) {
    spip_log("Erreur à la modification de la transaction campagnodon ".$id_campagnodon_transaction, "campagnodon"._LOG_ERREUR);
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
    'campaign_id' => $campagne['id_origine'],
    // 'payment_method' => 'transfer' // FIXME: use the correct value
  ]);

  // spip_log('Résultat CiviCRM: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

  if (!$result) {
    spip_log("Erreur CiviCRM " . $civi_api->errorMsg(), "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:erreur_sauvegarde")];
  }

  $update_campagnodon_transaction = [];
  $civicrm_result = $civi_api->values;
  if (empty($civicrm_result)) {
    $civicrm_result = [];
  }
  if (!empty($civicrm_result->donation)) {
    $update_campagnodon_transaction['id_don_distant'] = $civicrm_result->donation->id;
    civicrm_search_contact_id($civicrm_result->donation, $update_campagnodon_transaction);
  }
  if (!empty($civicrm_result->subscription)) {
    $update_campagnodon_transaction['id_adhesion_distant'] = $civicrm_result->subscription->id;
    civicrm_search_contact_id($civicrm_result->subscription, $update_campagnodon_transaction);
  }

  if (count($update_campagnodon_transaction) === 0 || false === sql_updateq(
    'spip_campagnodon_transactions',
    $update_campagnodon_transaction,
    'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
  )) {
    spip_log("Erreur à la modification de la transaction campagnodon ".$id_campagnodon_transaction." (insertion infos CiviCRM)", "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => _T("campagnodon:erreur_sauvegarde")];
  }

  return [
    'redirect' => generer_url_public('payer-acte', "id_transaction=$id_transaction&transaction_hash=$hash", false, false),
    'editable' => false,
  ];
}

/**
 * Cette fonction retourne la campagne si elle est bien valide.
 */
function get_campagne_ouverte($id_campagne) {
  $where = 'id_campagnodon_campagne='.sql_quote(intval($id_campagne));
  $where.= " AND statut='publie'";
  return sql_fetsel('*', 'spip_campagnodon_campagnes', $where);
}

/**
 * Cette fonction cherche le contact_id dans un sous-objet CiviCRM.
 */
function civicrm_search_contact_id($obj, &$update_campagnodon_transaction) {
  // spip_log(var_export($obj, true), "campagnodon"._LOG_DEBUG);
  if (!empty($update_campagnodon_transaction['id_contact_distant'])) {
    // On a déjà l'id.
    return;
  }
  if (empty($obj)) {
    return;
  }
  $id = strval($obj->id);
  if (empty($id)) {
    return;
  }
  if (empty($obj->values)) {
    return;
  }
  if (empty($obj->values->$id)) {
    return;
  }
  if (empty($obj->values->$id->contact_id)) {
    return;
  }
  $update_campagnodon_transaction['id_contact_distant'] = $obj->values->$id->contact_id;
}
