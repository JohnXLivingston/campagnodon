<?php
/**
 * Dans ce fichier, quelques fonctions qui permettent de construire le formulaire.
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Retourne les variables décrivant les types à mettre dans le formulaire (don et/ou adhésion).
 * @param string $type le type de formulaire (comme passé à la balise campagnodon)
 * @return array les types, et la valeur par défaut le cas échéant.
 */
function form_init_get_types($type) {
  $types = [];
  if ($type === 'don' || $type === 'don+adhesion') {
    $types[] = [
      'valeur' => 'don',
      'label' => _T('campagnodon_form:choix_don'),
    ];
  }
  if ($type === 'adhesion' || $type === 'don+adhesion') {
    $types[] = [
      'valeur' => 'adhesion',
      'label' => _T('campagnodon_form:choix_adhesion'),
    ];
  }

  $choix_type_defaut = '';
  if ($type === 'don') {
    $choix_type_defaut = 'don';
  } else if ($type === 'adhesion') {
    $choix_type_defaut = 'adhesion';
  }

  return [
    $types,
    $choix_type_defaut
  ];
}



/**
 * Retourne le montant (montant vs montant_libre, etc...)
 * @param $config_montants Correspond au retour de la fonction form_init_liste_montants_campagne
 */
function form_init_get_form_montant($config_montants) {
  $suffix = ''; // pour les dons récurrents, on a un suffix au nom du champs.
  if ($config_montants['don_recurrent'] === true) { // les dons récurrents sont bien activés sur ce formulaire
    if (_request('don_recurrent') == '1') { // la case don récurrent est cochée
      $suffix = '_recurrent';
    }
  }
  spip_log('form_init_get_form_montant: le suffixe est "'.$suffix.'"', 'campagnodon'._LOG_DEBUG);
  $v_montant = _request('montant'.$suffix);
  if ($v_montant === 'libre') {
    if (!$config_montants['libre'.$suffix]) {
      spip_log('Le champs est sur libre, mais la fonction n\'est pas active.', 'campagnodon'._LOG_DEBUG);
      return null;
    }
    $v_montant = trim(_request('montant_libre'.$suffix));
  } else {
    if (!array_key_exists($v_montant, $config_montants['propositions'.$suffix])) {
      return null;
    }
  }
  return [$v_montant, $suffix !== ''];
}


/**
 * Retourne le montant de l'adhésion
 * @param $config_montants Correspond au retour de la fonction form_init_liste_montants_campagne
 */
function form_init_get_form_montant_adhesion($config_montants) {
  $v_montant = _request('montant_adhesion');
  if (empty($config_montants['propositions_adhesion'])) {
    return null;
  }
  if (!array_key_exists($v_montant, $config_montants['propositions_adhesion'])) {
    return null;
  }
  return $v_montant;
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
function form_init_parse_config_montant(&$options, $montant) {
  if (is_array($montant)) {
    foreach ($montant as $m) {
      if (!form_init_parse_config_montant($options, $m)) { return false; }
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

function form_init_liste_montants_campagne($type, $id_campagne, $arg_liste_montants, $arg_don_recurrent, $arg_liste_montants_recurrent) {
  include_spip('inc/campagnodon.utils');
  $montants_par_defaut = campagnodon_montants_par_defaut($type);

  $r = [
    'propositions' => [],
    'libre' => false,
    'don_recurrent' => false,
    'uniquement_libre' => false,
    'propositions_adhesion' => null,
    'propositions_recurrent' => null,
    'libre_recurrent' => false,
    'uniquement_libre_recurrent' => false
  ];

  if ($type === 'adhesion') {
    $liste_montants = empty($arg_liste_montants) ? $montants_par_defaut : explode(',', $arg_liste_montants);
    $r['propositions_adhesion'] = [];
    $r['libre'] = true;
    $r['uniquement_libre'] = true;
    if (!form_init_parse_config_montant($r['propositions_adhesion'], $liste_montants)) {
      spip_log("Montant invalide dans la configuration du formulaire.", "campagnodon"._LOG_ERREUR);
      return false;
    }
    return $r;
  }

  // Nous somme sur des dons

  if (empty($arg_liste_montants)) {
    // On n'a rien personnalisé dans le formulaire courant, on prend la configuration serveur.
    if (!form_init_parse_config_montant($r['propositions'], $montants_par_defaut)) {
      spip_log("Montant invalide dans la configuration du formulaire.", "campagnodon"._LOG_ERREUR);
      return false;
    }
    $r['libre'] = true;
  } else {
    // on a donné des montants dans la configuration du formulaire, on les prend en compte.
    $liste = explode(',', $arg_liste_montants);
    foreach ($liste as $montant) {
      if ($montant === 'libre') {
        $r['libre'] = true;
        continue;
      }
      if (!form_init_parse_config_montant($r['propositions'], $montant)) {
        spip_log("Montant invalide dans la configuration du formulaire: '".$montant."'.", "campagnodon"._LOG_ERREUR);
        return false;
      }
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

  if ($arg_don_recurrent === '1' && campagnodon_don_recurrent_active()) {
    $r['don_recurrent'] = true;
    $r['propositions_recurrent'] = [];

    $montants_recurrent_par_defaut = campagnodon_montants_par_defaut($type.'_recurrent');
    if (empty($arg_liste_montants_recurrent)) {
      if (!form_init_parse_config_montant($r['propositions_recurrent'], $montants_recurrent_par_defaut)) {
        spip_log("Montant invalide dans la configuration du formulaire (recurrent).", "campagnodon"._LOG_ERREUR);
        return false;
      }
      $r['libre_recurrent'] = true;
    } else {
      $liste = explode(',', $arg_liste_montants_recurrent);
      foreach ($liste as $montant) {
        if ($montant === 'libre') {
          $r['libre_recurrent'] = true;
          continue;
        }
        if (!form_init_parse_config_montant($r['propositions_recurrent'], $montant)) {
          spip_log("Montant invalide dans la configuration du formulaire (recurrent): '".$montant."'.", "campagnodon"._LOG_ERREUR);
          return false;
        }
      }
    }
    if (count($r['propositions_recurrent']) === 0) {
      if ($r['libre_recurrent']) {
        $r['uniquement_libre_recurrent'] = true;
      } else {
        spip_log("Liste des montants vide.", "campagnodon"._LOG_ERREUR);
        return false;
      }
    }
  }

  return $r;
}

/**
 * Retourne les civilités à proposer dans le formulaire.
 */
function form_init_liste_civilites($mode_options) {
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

function form_init_get_adhesion_magazine_prix($mode_options, $type) {
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


function form_init_liste_souscriptions_optionnelles($type, $mode_options, $arg_souscriptions_perso) {
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
