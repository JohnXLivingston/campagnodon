<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

class CampagnodonException extends Exception {
  protected string $error_label = "";
  public function __construct(string $message = "", string $error_label, int $code = 0, ?Throwable $previous = null) {
    parent::__construct($message, $code, $previous);
    $this->error_label = $error_label;
  }

  public function getErrorLabel() {
    return _T($this->error_label);
  }
}

function liste_montants_campagne() {
  // TODO: pouvoir récupérer les montants depuis les paramètres du formulaire.
  return [
    'propositions' => [
      '13' => '13 €',
      '21' => '21 €',
      '35' => '35 €',
      '48' => '48 €',
      '65' => '65 €',
      '84' => '84 €',
      '120' => '120 €',
      '160' => '160 €',
    ],
    'libre' => false
  ];
}

function liste_civilites() {
  if (defined('_CAMPAGNODON_LISTE_CIVILITE') && is_array(_CAMPAGNODON_LISTE_CIVILITE)) {
    return _CAMPAGNODON_LISTE_CIVILITE;
  }
  return array(
    'M' => 'M.',
    'Mme' => 'Mme.',
    'Mx' => 'Mx.'
  );
}

function liste_souscriptions_optionnelles() {
  if (defined('_CAMPAGNODON_SOUSCRIPTIONS_OPTIONNELLES') && is_array(_CAMPAGNODON_SOUSCRIPTIONS_OPTIONNELLES)) {
    return _CAMPAGNODON_SOUSCRIPTIONS_OPTIONNELLES;
  }
  return array();
}

function traduit_financial_type($type) {
  if (
    defined('_CAMPAGNODON_TYPE_CONTRIBUTION')
    && is_array(_CAMPAGNODON_TYPE_CONTRIBUTION)
    && array_key_exists($type, _CAMPAGNODON_TYPE_CONTRIBUTION)
  ) {
    return _CAMPAGNODON_TYPE_CONTRIBUTION[$type];
  }
  return $type;
}

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

  $montants = liste_montants_campagne();
  $civilites = liste_civilites();
  $souscriptions_optionnelles = liste_souscriptions_optionnelles();
  
  $values = [
    /* Éléments statiques */
    '_montants_propositions' => $montants['propositions'],
    '_civilites' => $civilites,
    '_souscriptions_optionnelles' => $souscriptions_optionnelles,
    'montant' => '',
    'email' => '',
    'recu_fiscal' => '',
    'civilite' => '',
    'prenom' => '',
    'nom' => '',
    'date_naissance' => '',
    'adresse' => '',
    'code_postal' => '',
    'ville' => '',
    'pays' => defined('_CAMPAGNODON_PAYS_DEFAULT') ? _CAMPAGNODON_PAYS_DEFAULT : '',
    'telephone' => '',
  ];

  foreach ($souscriptions_optionnelles as $cle => $souscription_optionnelle) {
    $values['souscription_optionnelle_'.$cle] = '';
  } 
  
  return $values;
}

function formulaires_campagnodon_verifier_dist($type, $id_campagne=NULL) {
  $erreurs = [];
  $verifier = charger_fonction('verifier', 'inc/');
  
  $obligatoires = [];

  $campagne = get_campagne_ouverte($id_campagne);
  if (empty($campagne)) {
    $erreurs['message_erreur'] = _T('campagnodon:campagne_invalide');
  }

  $montants = liste_montants_campagne();
  $civilites = liste_civilites();
  $recu_fiscal = _request('recu_fiscal') == '1';
  
  $obligatoires = ['email']; // Pas besoin de 'montant', il sera testé plus loin
  if ($recu_fiscal) {
    array_push($obligatoires, 'prenom', 'nom', 'adresse', 'code_postal', 'ville', 'pays');
  }
  
  foreach ($obligatoires as $obligatoire) {
    if(!_request($obligatoire)) {
      $erreurs[$obligatoire] = _T('info_obligatoire');
    }
  }

  if ($e = _request('email') and !email_valide($e)) {
		$erreurs['email'] = _T('campagnodon_form:erreur_email_invalide');
	}

  $montant = _request('montant');
  if ($erreur = $verifier($montant, 'entier', array('min' => 1, 'max' => 10000000))) {
    $erreurs['montant'] = $erreur;
  }
  // TODO: implémenter le montant libre (et fixer une borne ?)
  if (!array_key_exists($montant, $montants['propositions'])) {
    $erreurs['montant'] = _T('info_obligatoire');
  }

  if ($recu_fiscal) {
    $pays = _request('pays');
    $ret = sql_select('nom', 'spip_pays', 'code='.sql_quote($pays));
		if (sql_count($ret) != 1) {
			$erreurs['pays'] = _T('campagnodon_form:erreur_pays_invalide');
		}

    $civilite = _request('civilite');
    if (!empty($civilite) && !array_key_exists($civilite, $civilites)) {
      $erreurs['civilite'] = _T('campagnodon_form:erreur_civilite_invalide');
    }

    $date_naissance = _request('date_naissance');
    if (!empty($date_naissance)) {
      if ($erreur = $verifier($date_naissance, 'date', array('format' => 'amj'))) {
        $erreurs['date_naissance'] = $erreur;
      }
    }

    $telephone = _request('telephone');
    if (!empty($telephone)) {
      if ($erreur = $verifier($telephone, 'telephone')) {
        $erreurs['telephone'] = $erreur;
      }
    }
  }

  // TODO: ajouter les tests manquants sur les données (email, code postal, champs divers, etc...)
  return $erreurs;
}

function formulaires_campagnodon_traiter_dist($type, $id_campagne=NULL) {
  try {
    spip_log("traiter_dist" . $type . ":". $id_campagne);
    if (_CAMPAGNODON_MODE !== 'civicrm') {
      throw new CampagnodonException("Campagnodon Non configuré, constante _CAMPAGNODON_MODE manquante.", "campagnodon:erreur_sauvegarde");
    }
    if (!defined('_CAMPAGNODON_CIVICRM_API_OPTIONS')) {
      throw new CampagnodonException("CiviCRM Non configuré, constante _CAMPAGNODON_CIVICRM_API_OPTIONS manquante.", "campagnodon:erreur_sauvegarde");
    }

    $campagne = get_campagne_ouverte($id_campagne);
    if (empty($campagne)) {
      throw new CampagnodonException('Campagne invalide au moment de l\'enregistrement', "campagnodon:campagne_invalide");
    }
    if ($campagne['origine'] !== 'civicrm') {
      throw new CampagnodonException('La campagne n\'a pas CiviCRM pour origine.', "campagnodon:campagne_invalide");
    }

    $id_campagnodon_transaction = sql_insertq('spip_campagnodon_transactions', [
      'id_campagnodon_campagne' => $id_campagne,
      'type_distant' => 'civicrm'
    ]);
    if (!($id_campagnodon_transaction > 0)) {
      throw new CampagnodonException("Erreur à la création de la transaction campagnodon.", "campagnodon:erreur_sauvegarde");
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
      throw new CampagnodonException("Erreur à la création de la transaction ".$id_campagnodon_transaction, "campagnodon:erreur_sauvegarde");
    }

    include_spip('inc/campagnodon.utils');
    $transaction_idx_distant = get_transaction_idx_distant($id_campagnodon_transaction);
    if (false === sql_updateq(
      'spip_campagnodon_transactions',
      [
        'id_transaction' => $id_transaction,
        'transaction_distant' => $transaction_idx_distant
      ],
      'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
    )) {
      throw new CampagnodonException("Erreur à la modification de la transaction campagnodon ".$id_campagnodon_transaction, "campagnodon:erreur_sauvegarde");
    }

    include_spip('inc/civicrm/class.api');
    $civi_api = new civicrm_api3(_CAMPAGNODON_CIVICRM_API_OPTIONS);

    $params = array(
      'email' => _request('email'),
      'contributions' => [
        [
          'financial_type' => traduit_financial_type('don'),
          'amount' => _request('montant')
        ]
      ],
      'campaign_id' => $campagne['id_origine'],
      'transaction_idx' => $transaction_idx_distant
      // 'payment_method' => 'transfer' // FIXME: use the correct value
      // TODO: recu fiscal
    );
    if (_request('recu_fiscal') == '1') {
      // FIXME: je n'ai pas réussi à faire marcher la phase de normalisation ci-dessous.
      // $date_naissance = _request('date_naissance');
      // $date_naissance_normalisee = null;
      // if (!empty($date_naissance)) {
      //   $verifier = charger_fonction('verifier', 'inc/');
      //   $verifier($date_naissance, 'date', array('format' => 'amj', 'normaliser' => 'date'), $date_naissance_normalisee);
      // }

      $params = array_merge($params, array(
        'prefix' => _request('civilite'), // TODO: cabler dans l'API coté CiviCRM.
        'first_name' => _request('prenom'),
        'last_name' => _request('nom'),
        'birth_date' => _request('date_naissance'), // TODO: cabler dans l'API coté CiviCRM
        'street_address' => _request('adresse'),
        'postal_code' => _request('code_postal'),
        'city' => _request('ville'),
        'country' => _request('pays'), // FIXME: vérifier que les valeurs sont bien compatibles avec celles de l'api CiviCRM.
        'phone' => _request('telephone'), // TODO: cabler dans l'API coté CiviCRM
      ));

      // spip_log('Params contact CiviCRM: ' . json_encode($params), 'campagnodon'._LOG_DEBUG);
    }

    $souscriptions_optionnelles = liste_souscriptions_optionnelles();
    foreach ($souscriptions_optionnelles as $cle => $souscription_optionnelle) {
      if (!empty($souscription_optionnelle['cle_distante'])) {
        $params[$souscription_optionnelle['cle_distante']] = _request('souscription_optionnelle_'.$cle) == '1';
      }
    }

    // $result = $civi_api->Attac->create_member($params);
    $result = $civi_api->Campagnodon->create($params);

    // spip_log('Résultat CiviCRM: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

    if (!$result) {
      throw new CampagnodonException("Erreur CiviCRM " . $civi_api->errorMsg(), "campagnodon:erreur_sauvegarde");
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
      throw new CampagnodonException("Erreur à la modification de la transaction campagnodon ".$id_campagnodon_transaction." (insertion infos CiviCRM)", "campagnodon:erreur_sauvegarde");
    }

    return [
      'redirect' => generer_url_public('payer-acte', "id_transaction=$id_transaction&transaction_hash=$hash", false, false),
      'editable' => false,
    ];
  } catch (CampagnodonException $e) {
    spip_log($e->getMessage(), "campagnodon"._LOG_ERREUR);
    return ['message_erreur' => $e->getErrorLabel()];
  }
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
