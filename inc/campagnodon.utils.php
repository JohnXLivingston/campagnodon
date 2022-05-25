<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Cette fonction retourne l'identifiant à placer dans le champs transaction_idx de CiviCRM.
 */
function get_transaction_idx_distant($mode_options, $id_campagnodon_transaction) {
  if (
    is_array($mode_options)
    && array_key_exists('prefix', $mode_options)
    && is_string($mode_options['prefix'])
  ) {
      $prefix = $mode_options['prefix'];
  } else {
    $prefix = 'campagnodon';
  }
  return $prefix . '/' . $id_campagnodon_transaction;
}

/**
 * Retourne, le cas échanté, la fonction de connecteur à utiliser.
 * @param string $mode
 *  Le mode utilisé (la clé dans _CAMPAGNODON_MODES).
 * @param string $nom_fonction
 *  Le nom de la fonction connecteur souhaitée.
 */
function campagnodon_fonction_connecteur($mode, $nom_fonction) {
  if (!defined('_CAMPAGNODON_MODES') || !is_array(_CAMPAGNODON_MODES)) {
    return false;
  }
  if (!array_key_exists($mode, _CAMPAGNODON_MODES)) {
    return false;
  }
  $type = _CAMPAGNODON_MODES[$mode]['type'];
  if (!preg_match('/^[a-z]+$/', $type)) {
    return false;
  }
  if (!preg_match('/^[a-z0-9_]+$/', $nom_fonction)) {
    return false;
  }
  return charger_fonction($nom_fonction, 'inc/campagnodon/connecteur/'.$type);
}

function campagnodon_mode_options($mode) {
  if (!defined('_CAMPAGNODON_MODES') || !is_array(_CAMPAGNODON_MODES)) {
    return array();
  }
  if (!array_key_exists($mode, _CAMPAGNODON_MODES)) {
    return array();
  }
  return _CAMPAGNODON_MODES[$mode];
}

function _traduit_type_paiement($mode_options, $type) {
  if (
    is_array($mode_options)
    && array_key_exists('type_paiement', $mode_options)
    && is_array($mode_options['type_paiement'])
    && array_key_exists($type, $mode_options['type_paiement'])
  ) {
    return $mode_options['type_paiement'][$type];
  }
  return $type;
}

function _traduit_statut_paiement($mode_options, $statut) {
  if (
    is_array($mode_options)
    && array_key_exists('statut_paiement', $mode_options)
    && is_array($mode_options['statut_paiement'])
    && array_key_exists($statut, $mode_options['statut_paiement'])
  ) {
    return $mode_options['statut_paiement'][$statut];
  }
  return $statut;
}

function campagnodon_maj_sync_statut($id_campagnodon_transaction, $status) {
  sql_update('spip_campagnodon_transactions', [
    'statut_synchronisation' => sql_quote($status),
    'date_synchronisation' => 'NOW()'
  ], 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
}

/**
 * Synchronise une transaction avec le système distant.
 * En cas d'échec (système injoignable par ex), replanifie une synchronisation.
 * @param $id_campagnodon_transaction
 */
function campagnodon_synchroniser_transaction($id_campagnodon_transaction, $nb_tentatives = 0) {
  spip_log('campagnodon_synchroniser_transaction: appel id_campagnodon_transaction='.$id_campagnodon_transaction.', tentative #'.$nb_tentatives.'.', 'campagnodon'._LOG_DEBUG);

  $campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
  if (!$campagnodon_transaction) {
    spip_log("spip_campagnodon_transactions introuvable: ".$id_campagnodon_transaction, "campagnodon"._LOG_ERREUR);
    return 0;
  }
  $transaction = sql_fetsel('*', 'spip_transactions', 'id_transaction=' . intval($campagnodon_transaction['id_transaction']));
  if (!$transaction) {
    spip_log("Transaction introuvable pour spip_campagnodon_transactions=".$id_campagnodon_transaction, "campagnodon"._LOG_ERREUR);
    campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
    return 0;
  }

  $mode = $campagnodon_transaction['mode'];
  $mode_options = campagnodon_mode_options($mode);

  $statut = $transaction['statut'];
  $statut_distant = null;
  // Parfois spip bank ajoute des choses derrière les statuts. Par ex «echec[fail]». Dans le doute, on applique cette règle pour tous les statuts.
  if (strncmp($statut, 'ok', 2) == 0) {
    $statut_distant = 'ok';
  } else if (strncmp($statut, 'echec', 5) == 0) {
    $statut_distant = 'echec';
  } else if (strncmp($statut, 'commande', 8) == 0) {
    $statut_distant = 'attente'; // FIXME: ou alors il faut différencier commande et attente coté CiviCRM ?
  } else if (strncmp($statut, 'attente', 7) == 0) {
    $statut_distant = 'attente';
  } else if (strncmp($statut, 'abandon', 7) == 0) {
    $statut_distant = 'abandon';
  } else if (strncmp($statut, 'rembourse', 8) == 0) {
    $statut_distant = 'rembourse';
  } else {
    spip_log("Je ne sais pas synchroniser le statut '".$statut."' pour spip_campagnodon_transactions=".$id_campagnodon_transaction, "campagnodon"._LOG_ERREUR);
    campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
    return 0;
  }
  $statut_paiement_distant = _traduit_statut_paiement($mode_options, $statut_distant);

  $mode_paiement = $transaction['mode'];
  if (empty($mode_paiement)) {
    // on peut ne pas encore avoir de mode de paiement, auquel cas on ne le synchronise pas.
    $mode_paiement_distant = null;
  } elseif (preg_match('/^([^\/]*)/', $mode_paiement, $matches)) {
    $mode_paiement_distant = _traduit_type_paiement($mode_options, $matches[1]);
  } else {
    spip_log("Je ne sais pas synchroniser le mode '".$mode_paiement."' pour spip_campagnodon_transactions=".$id_campagnodon_transaction, "campagnodon"._LOG_ERREUR);
    campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
    return 0;
  }

  $fonction_maj_statut = campagnodon_fonction_connecteur($mode, 'maj_statut');
  if (!$fonction_maj_statut) {
    spip_log('Campagnodon mal configuré, impossible de trouver le connecteur nouvelle_contribution pour le mode: '.$mode, "campagnodon"._LOG_ERREUR);
    campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
    return 0;
  }

  $failed = false;
  try {
    if (false === $fonction_maj_statut($mode_options, $campagnodon_transaction['transaction_distant'], $statut_distant, $statut_paiement_distant, $mode_paiement_distant)) {
      spip_log("Il semblerait que la synchronisation a échoué pour spip_campagnodon_transactions=".$id_campagnodon_transaction, "campagnodon"._LOG_ERREUR);
      $failed = true;
    } else {
      spip_log('campagnodon_synchroniser_transaction: appel id_campagnodon_transaction='.$id_campagnodon_transaction.', tentative #'.$nb_tentatives.', le connecteur a réussi à mettre à jour.', 'campagnodon'._LOG_DEBUG);
    }
  } catch (Exception $e) {
    spip_log("Il semblerait que la synchronisation a échoué pour spip_campagnodon_transactions=".$id_campagnodon_transaction.": ".$e->getMessage(), "campagnodon"._LOG_ERREUR);
    $failed = true;
  }

  if ($failed) {
    if ($nb_tentatives > 10) {
      spip_log("La synchronisation ayant échoué pour spip_campagnodon_transactions=".$id_campagnodon_transaction.", et j'ai dépassé le nombre de tentatives max.", "campagnodon"._LOG_ERREUR);
      campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
      return 0;
    }
    spip_log("La synchronisation ayant échoué pour spip_campagnodon_transactions=".$id_campagnodon_transaction.", je replanifie une synchro.", "campagnodon"._LOG_ERREUR);
    campagnodon_maj_sync_statut($id_campagnodon_transaction, 'attente_rejoue');
    job_queue_add(
      'campagnodon_synchroniser_transaction',
      'Campagnodon - Synchronisation de la transaction '.$id_transaction. ' (tentative n°'.$nb_tentatives.' après échec)',
      [$id_campagnodon_transaction, $nb_tentatives + 1],
      'inc/campagnodon.utils',
      false, // on autorise la création, de tâches duplicate. Vaut mieux synchroniser plus que nécessaire, pour éviter les effets de bords.
      time() + ($nb_tentatives * 120),
      -10
    );
    return 0;
  }

  spip_log('campagnodon_synchroniser_transaction: appel id_campagnodon_transaction='.$id_campagnodon_transaction.', tentative #'.$nb_tentatives.', tout est ok.', 'campagnodon'._LOG_DEBUG);
  campagnodon_maj_sync_statut($id_campagnodon_transaction, 'ok');
  return 1;
}
