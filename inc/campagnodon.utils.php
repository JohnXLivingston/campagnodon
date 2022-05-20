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
