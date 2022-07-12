<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

class CampagnodonException extends Exception {
  protected $error_label = "";
  public function __construct($message = "", $error_label, $code = 0, $previous = null) {
    parent::__construct($message, $code, $previous);
    $this->error_label = $error_label;
  }

  public function getErrorLabel() {
    return _T($this->error_label);
  }
}

/**
 * Pour configurer les montants, on a une liste qui peut avoir l'une des forme suivante:
 * - 12,25,40
 * - 10[-1000],20[1000-2000]
 * Le premier format génère une liste avec des libellés du type «12€».
 * Le deuxième, «12€ (entre X et Y de revenus mensuels)».
 * Cette fonction parse une valeur, et ajoute l'option à la liste de propositions la valeur et le libellé correspondant.
 * @param $options Le tableau cle=>valeur des options
 * @param $montant Un (ou plusieurs) fragment(s) de la config («12» ou «20[1000-2000]).
 */
function parse_config_montant(&$options, $montant) {
  if (is_array($montant)) {
    foreach ($montant as $m) {
      if (!parse_config_montant($options, $m)) { return false; }
    }
    return true;
  }

  $montant = trim($montant);
  if (!preg_match('/^(?<montant>\d+)(?:\[(?<min>\d+)?-(?<max>\d+)?\])?$/', $montant, $matches)) {
    spip_log("Montant invalide dans la configuration du formulaire: '".$montant."'.", "campagnodon"._LOG_ERREUR);
    return false;
  }
  $v = $matches['montant'];
  $label = '';
  if (empty($matches['min']) && empty($matches['max'])) {
    $label = $v . ' €';
  } else if (empty($matches['min'])) {
    $label = _T('campagnodon_form:option_revenu_en_dessous', array(
      'montant' => $v,
      'max' => $matches['max']
    ));
  } else if (empty($matches['max'])) {
    $label = _T('campagnodon_form:option_revenu_au_dessus', array(
      'montant' => $v,
      'min' => $matches['min']
    ));
  } else {
    $label = _T('campagnodon_form:option_revenu_entre', array(
      'montant' => $v,
      'min' => $matches['min'],
      'max' => $matches['max']
    ));
  }
  $options[$v] = $label;
  return true;
}

function liste_montants_campagne($type, $id_campagne, $arg_liste_montants) {
  include_spip('inc/campagnodon.utils');
  $montants_par_defaut = campagnodon_montants_par_defaut($type);

  if ($type === 'adhesion') {
    $liste_montants = empty($arg_liste_montants) ? $montants_par_defaut : explode(',', $arg_liste_montants);
    $propositions_adhesion = [];
    if (!parse_config_montant($propositions_adhesion, $liste_montants)) {
      spip_log("Montant invalide dans la configuration du formulaire: '".$montant."'.", "campagnodon"._LOG_ERREUR);
      return false;
    }
    return [
      'propositions' => [],
      'libre' => true,
      'uniquement_libre' => true,
      'propositions_adhesion' => $propositions_adhesion
    ];
  }

  if (empty($arg_liste_montants)) {
    $propositions = [];
    if (!parse_config_montant($propositions, $montants_par_defaut)) {
      spip_log("Montant invalide dans la configuration du formulaire: '".$montant."'.", "campagnodon"._LOG_ERREUR);
      return false;
    }
    return [
      'propositions' => $propositions,
      'libre' => true,
      'uniquement_libre' => false,
      'propositions_adhesion' => null
    ];
  }

  $r = [
    'propositions' => [],
    'libre' => false,
    'uniquement_libre' => false,
    'propositions_adhesion' => null
  ];
  $liste = explode(',', $arg_liste_montants);
  foreach ($liste as $montant) {
    if ($montant === 'libre') {
      $r['libre'] = true;
      continue;
    }
    if (!parse_config_montant($r['propositions'], $montant)) {
      spip_log("Montant invalide dans la configuration du formulaire: '".$montant."'.", "campagnodon"._LOG_ERREUR);
      return false;
    }
  }

  if (count($r['propositions']) === 0) {
    if ($r['libre']) {
      $r['uniquement_libre'] = true;
    } else {
      spip_log("Liste des montants vide.", "campagnodon"._LOG_ERREUR);
      return false;
    }
  }
  
  return $r;
}

/**
 * Retourne le montant (montant vs montant_libre, etc...)
 * @param $config_montants Correspond au retour de la fonction liste_montants_campagne
 */
function get_form_montant($config_montants) {
  $v_montant = _request('montant');
  if ($v_montant === 'libre') {
    if (!$config_montants['libre']) {
      return null;
    }
    return trim(_request('montant_libre'));
  }
  if (!array_key_exists($v_montant, $config_montants['propositions'])) {
    return null;
  }
  return $v_montant;
}


/**
 * Retourne le montant de l'adhésion
 * @param $config_montants Correspond au retour de la fonction liste_montants_campagne
 */
function get_form_montant_adhesion($config_montants) {
  $v_montant = _request('montant_adhesion');
  if (empty($config_montants['propositions_adhesion'])) {
    return null;
  }
  if (!array_key_exists($v_montant, $config_montants['propositions_adhesion'])) {
    return null;
  }
  return $v_montant;
}

function liste_civilites($mode_options) {
  if (is_array($mode_options) && array_key_exists('liste_civilites', $mode_options)) {
    // Pour des soucis de cohérence dans la config, il faut ici inverser le sens key=>val
    $l = array();
    foreach ($mode_options['liste_civilites'] as $k => $v) {
      $l[$v] = $k;
    }
    return $l;
  }
  return array(
    'M' => 'M.',
    'Mme' => 'Mme.',
    'Mx' => 'Mx.'
  );
}

function liste_souscriptions_optionnelles($type, $mode_options, $arg_souscriptions_perso) {
  if (!is_array($mode_options) || !array_key_exists('souscriptions_optionnelles', $mode_options)) {
    return array();
  }
  $r = array();
  // 2 modes: soit on prend la config par défaut, doit on a personnalisé via la balise
  if (empty($arg_souscriptions_perso)) {
    foreach ($mode_options['souscriptions_optionnelles'] as $k => $so) {
      if (!array_key_exists('pour', $so) || empty($so['pour'])) {
        $r[$k] = $so;
      } else if (is_array($so['pour']) && false !== array_search($type, $so['pour'], true)) {
        $r[$k] = $so;
      }
    }
  } else {
    $souscriptions_perso = explode(',', $arg_souscriptions_perso);
    foreach ($souscriptions_perso as $k) {
      if (!array_key_exists($k, $mode_options['souscriptions_optionnelles'])) {
        continue;
      }
      $so = $mode_options['souscriptions_optionnelles'][$k];
      if (!is_array($so)) {
        continue;
      }
      if (!array_key_exists('pour', $so) || empty($so['pour'])) {
        $r[$k] = $so;
      } else if (is_array($so['pour'])) {
        if (
          false !== array_search($type, $so['pour'], true)
          || false !== array_search($type.'?', $so['pour'], true)
        ) {
          $r[$k] = $so;
        }
      }
    }
  }
  return $r;
}

function traduit_financial_type($mode_options, $type) {
  if (
    is_array($mode_options)
    && array_key_exists('type_contribution', $mode_options)
    && is_array($mode_options['type_contribution'])
    && array_key_exists($type, $mode_options['type_contribution'])
  ) {
    return $mode_options['type_contribution'][$type];
  }
  return $type;
}

function traduit_adhesion_type($mode_options, $type) {
  if (
    is_array($mode_options)
    && array_key_exists('adhesion_type', $mode_options)
    && is_array($mode_options['adhesion_type'])
    && array_key_exists($type, $mode_options['adhesion_type'])
  ) {
    return $mode_options['adhesion_type'][$type];
  }
  return $type;
}

function get_adhesion_magazine_prix($mode_options, $type) {
  if (
    $type === 'adhesion'
    && is_array($mode_options)
    && array_key_exists('adhesion_magazine_prix', $mode_options)
    && ! empty($mode_options['adhesion_magazine_prix'])
  ) {
    return intval($mode_options['adhesion_magazine_prix']);
  }
  return 0;
}

/**
* Declarer les champs postes et y integrer les valeurs par defaut
*/
function formulaires_campagnodon_charger_dist($type, $id_campagne=NULL, $arg_liste_montants=NULL, $arg_souscriptions_perso=NULL) {
  if ($type !== 'don' && $type !== 'adhesion') {
    spip_log("Type de Campagnodon inconnu: ".$type, "campagnodon"._LOG_ERREUR);
    return false;
  }

  $campagne = get_campagne_ouverte($id_campagne);
  if (empty($campagne)) {
    spip_log("Campagne introuvable: ".$id_campagne, "campagnodon"._LOG_ERREUR);
    return false;
  }

  include_spip('inc/campagnodon.utils');
  $mode_options = campagnodon_mode_options($campagne['origine']);

  $config_montants = liste_montants_campagne($type, $id_campagne, $arg_liste_montants);
  if (!$config_montants) {
    spip_log("Liste de montants invalide", "campagnodon"._LOG_ERREUR);
    return false;
  }
  $civilites = liste_civilites($mode_options);
  $souscriptions_optionnelles = liste_souscriptions_optionnelles($type, $mode_options, $arg_souscriptions_perso);
  
  $values = [
    '_type' => $type,
    '_montants_propositions' => $config_montants['propositions'],
    '_montants_proposition_libre' => $config_montants['libre'],
    '_montants_propositions_uniquement_libre' => $config_montants['uniquement_libre'],
    '_montants_propositions_adhesion' => $config_montants['propositions_adhesion'],
    '_civilites' => $civilites,
    '_souscriptions_optionnelles' => $souscriptions_optionnelles,
    'montant' => '',
    'montant_libre' => '',
    'email' => '',
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

  if ($type === 'adhesion') {
    $values['adhesion_avec_don'] = '';
    $adhesion_magazine_prix = get_adhesion_magazine_prix($mode_options, $type);
    if ($adhesion_magazine_prix > 0) {
      $values['_adhesion_magazine'] = true;
      $values['_adhesion_magazine_prix'] = $adhesion_magazine_prix;
    } else {
      $values['_adhesion_magazine'] = false;
    }
  } else {
    $values['recu_fiscal'] = '';
  }

  foreach ($souscriptions_optionnelles as $cle => $souscription_optionnelle) {
    $values['souscription_optionnelle_'.$cle] = '';
  } 
  
  return $values;
}

function formulaires_campagnodon_verifier_dist($type, $id_campagne=NULL, $arg_liste_montants=NULL, $arg_souscriptions_perso=NULL) {
  $erreurs = [];
  $verifier = charger_fonction('verifier', 'inc/');
  
  $obligatoires = [];

  $campagne = get_campagne_ouverte($id_campagne);
  if (empty($campagne)) {
    $erreurs['message_erreur'] = _T('campagnodon:campagne_invalide');
  }

  include_spip('inc/campagnodon.utils');
  $mode_options = campagnodon_mode_options($campagne['origine']);

  $config_montants = liste_montants_campagne($type, $id_campagne, $arg_liste_montants);
  $civilites = liste_civilites($mode_options);
  $recu_fiscal = $type === 'adhesion' || _request('recu_fiscal') == '1'; // on veut toujours un reçu pour les adhésions
  $adhesion_avec_don = $type === 'adhesion' && _request('adhesion_avec_don') == '1';
  $adhesion_magazine_prix = get_adhesion_magazine_prix($mode_options, $type);
  
  $obligatoires = ['email']; // Pas besoin de 'montant', il sera testé plus loin
  if ($recu_fiscal || $adhesion_avec_don) {
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

  $montant = get_form_montant($config_montants);
  if ($type !== 'adhesion' || $adhesion_avec_don) {
    if (empty($montant)) {
      $erreurs['montant'] = _T('info_obligatoire');
    } else if ($erreur = $verifier($montant, 'entier', array('min' => 1, 'max' => 10000000))) {
      $erreurs['montant'] = $erreur;
    }
  }

  $montant_adhesion = null;
  if ($type === 'adhesion') {
    $montant_adhesion = get_form_montant_adhesion($config_montants);
    if (empty($montant_adhesion)) {
      $erreurs['montant_adhesion'] = _T('info_obligatoire');
    } else if ($erreur = $verifier($montant_adhesion, 'entier', array('min' => 1 + $adhesion_magazine_prix, 'max' => 10000000))) {
      $erreurs['montant_adhesion'] = $erreur;
    }
  }

  if ($recu_fiscal || $type === 'adhesion') {
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

    $code_postal = _request('code_postal');
    if (!empty($code_postal)) {
      if ($erreur = $verifier($code_postal, 'code_postal', array('pays' => $pays))) {
        $erreurs['code_postal'] = $erreur;
      }
    }

    $telephone = _request('telephone');
    if (!empty($telephone)) {
      if ($erreur = $verifier($telephone, 'telephone')) {
        $erreurs['telephone'] = $erreur;
      }
    }
  }

  return $erreurs;
}

function formulaires_campagnodon_traiter_dist($type, $id_campagne=NULL, $arg_liste_montants=NULL, $arg_souscriptions_perso=NULL) {
  try {
    spip_log("traiter_dist" . $type . ":". $id_campagne);

    $campagne = get_campagne_ouverte($id_campagne);
    if (empty($campagne)) {
      throw new CampagnodonException('Campagne invalide au moment de l\'enregistrement', "campagnodon:campagne_invalide");
    }

    $config_montants = liste_montants_campagne($type, $id_campagne, $arg_liste_montants);
    $recu_fiscal = $type === 'adhesion' || _request('recu_fiscal') == '1'; // on veut toujours un reçu pour les adhésions
    $adhesion_avec_don = $type === 'adhesion' && _request('adhesion_avec_don') == '1';
    $montant = ($type !== 'adhesion' || $adhesion_avec_don) ? get_form_montant($config_montants) : null;
    $montant_adhesion = ($type === 'adhesion') ? get_form_montant_adhesion($config_montants) : null;

    $montant_total = 0;
    if ($montant) { $montant_total+= $montant; }
    if ($montant_adhesion) { $montant_total+= $montant_adhesion; }

    include_spip('inc/campagnodon.utils');
    $mode_options = campagnodon_mode_options($campagne['origine']);
    if (!$mode_options['type']) {
      throw new CampagnodonException("Campagnodon non configuré, mode inconnu: '".$campagne['origine']."'.", "campagnodon:erreur_sauvegarde");
    }
    $fonction_nouvelle_contribution = campagnodon_fonction_connecteur($campagne['origine'], 'nouvelle_contribution');
    if (!$fonction_nouvelle_contribution) {
      throw new CampagnodonException("Campagnodon mal configuré, impossible de trouver le connecteur nouvelle_contribution pour le mode: '".$campagne['origine']."'.", "campagnodon:erreur_sauvegarde");
    }

    $adhesion_magazine_prix = get_adhesion_magazine_prix($mode_options, $type);

    $id_campagnodon_transaction = sql_insertq('spip_campagnodon_transactions', [
      'id_campagnodon_campagne' => $id_campagne,
      'type_transaction' => $type,
      'mode' => $campagne['origine']
    ]);
    if (!($id_campagnodon_transaction > 0)) {
      throw new CampagnodonException("Erreur à la création de la transaction campagnodon.", "campagnodon:erreur_sauvegarde");
    }

    $inserer_transaction = charger_fonction('inserer_transaction', 'bank');
    $transaction_options = [
      // 'auteur' => _request('email'), // FIXME: peut-on se passer de cette info ?
      'parrain' => 'campagnodon',
      'tracking_id' => $id_campagnodon_transaction,
      'force' => true
    ];
    if (!(
      $id_transaction = $inserer_transaction($montant_total, $transaction_options)
      and $hash = sql_getfetsel('transaction_hash', 'spip_transactions', 'id_transaction=' . intval($id_transaction))
    )) {
      throw new CampagnodonException("Erreur à la création de la transaction ".$id_campagnodon_transaction, "campagnodon:erreur_sauvegarde");
    }

    $url_paiement = generer_url_public('payer', "id_transaction=$id_transaction&transaction_hash=$hash", true, false);

    include_spip('inc/campagnodon.utils');
    $transaction_idx_distant = get_transaction_idx_distant($mode_options, $id_campagnodon_transaction);
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

    $souscriptions_optionnelles = liste_souscriptions_optionnelles($type, $mode_options, $arg_souscriptions_perso);

    $contributions = null;
    if ($type === 'don') {
      $contributions = [
        [
          'financial_type' => traduit_financial_type($mode_options, 'don'),
          'amount' => $montant,
          'currency' => 'EUR'
        ]
      ];
    } else if ($type === 'adhesion') {
      $contributions = [];
      if ($adhesion_magazine_prix > 0) {
        $contribution_magazine = [
          'financial_type' => traduit_financial_type($mode_options, 'adhesion'),
          'amount' => strval($adhesion_magazine_prix),
          'currency' => 'EUR',
          'membership' => traduit_adhesion_type($mode_options, 'magazine')
        ];

        // On cherche l'éventuel souscription optionnelle special:magazine_pdf.
        foreach ($souscriptions_optionnelles as $cle => $so) {
          if ($so['type'] !== 'special:magazine_pdf') { continue; }
          $pdf_seulement_champ = $so['cle_distante'];
          // FIXME: la façon dont est passé ce paramètre n'est vraiment pas propre.
          $contribution_magazine['membership_option'] = $pdf_seulement_champ.':'.(_request('souscription_optionnelle_'.$cle) == '1' ? '1' : '0');
        }

        $contributions[] = $contribution_magazine;
      }

      $contributions[] = [
        'financial_type' => traduit_financial_type($mode_options, 'adhesion'),
        'amount' => strval(intval($montant_adhesion) - intval($adhesion_magazine_prix)),
        'currency' => 'EUR',
        'membership' => traduit_adhesion_type($mode_options, 'adhesion')
      ];

      if ($adhesion_avec_don) {
        $contributions[] = [
          'financial_type' => traduit_financial_type($mode_options, 'don'),
          'amount' => $montant,
          'currency' => 'EUR'
        ];
      }
    } else {
      throw new CampagnodonException("Type inconnu: '".$type."'");
    }

    $params = array(
      'campagnodon_version' => '1', // numéro de version pour le format de donnée (pour s'assurer de la compatibilité des API)
      'email' => trim(_request('email')),
      'contributions' => $contributions,
      'campaign_id' => $campagne['id_origine'],
      'transaction_idx' => $transaction_idx_distant,
      'payment_url' => $url_paiement,
      'optional_subscriptions' => array()
      // 'payment_method' => 'transfer' // FIXME: use the correct value
    );
    if ($recu_fiscal || $adhesion_avec_don) {
      // FIXME: je n'ai pas réussi à faire marcher la phase de normalisation ci-dessous.
      // $date_naissance = _request('date_naissance');
      // $date_naissance_normalisee = null;
      // if (!empty($date_naissance)) {
      //   $verifier = charger_fonction('verifier', 'inc/');
      //   $verifier($date_naissance, 'date', array('format' => 'amj', 'normaliser' => 'date'), $date_naissance_normalisee);
      // }

      $params = array_merge($params, array(
        'tax_receipt' => true,
        'prefix' => _request('civilite'),
        'first_name' => trim(_request('prenom')),
        'last_name' => trim(_request('nom')),
        'birth_date' => _request('date_naissance'),
        'street_address' => trim(_request('adresse')),
        'postal_code' => trim(_request('code_postal')),
        'city' => trim(_request('ville')),
        'country' => _request('pays'), // FIXME: vérifier que les valeurs sont bien compatibles avec celles de l'api CiviCRM.
        'phone' => trim(_request('telephone'))
      ));

      // spip_log('Params contact CiviCRM: ' . json_encode($params), 'campagnodon'._LOG_DEBUG);
    }

    foreach ($souscriptions_optionnelles as $cle => $souscription_optionnelle) {
      if (_request('souscription_optionnelle_'.$cle) == '1') {
        if (!$souscription_optionnelle['type']) {
          throw new CampagnodonException("Campagnodon mal configuré, souscription_optionnelle mal configurée: '".$cle."'.", "campagnodon:erreur_sauvegarde");
        }
        if (substr($souscription_optionnelle['type'], 0, 8) === 'special:') {
          // Cas particulier, on passe.
          continue;
        }
        $params['optional_subscriptions'][] = array(
          'type' => $souscription_optionnelle['type'],
          'key' => $souscription_optionnelle['cle_distante'],
          'when' => $souscription_optionnelle['when'] ?? 'init'
        );
      }
    }

    try {
      $result = $fonction_nouvelle_contribution($mode_options, $params);
    } catch (Exception $e) {
      throw new CampagnodonException("Erreur nouvelle_contribution mode=".$campagne['origine'].": " . $e->getMessage(), "campagnodon:erreur_sauvegarde");
    }

    // $update_campagnodon_transaction = [];
    // if (count($update_campagnodon_transaction) === 0 || false === sql_updateq(
    //   'spip_campagnodon_transactions',
    //   $update_campagnodon_transaction,
    //   'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
    // )) {
    //   throw new CampagnodonException("Erreur à la modification de la transaction campagnodon ".$id_campagnodon_transaction." (insertion infos CiviCRM)", "campagnodon:erreur_sauvegarde");
    // }

    return [
      'redirect' => $url_paiement,
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
