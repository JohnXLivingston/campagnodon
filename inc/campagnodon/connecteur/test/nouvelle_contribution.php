<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Créé la contribution en statut «en attente» dans le système distant.
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $params
 *  Les données à envoyer.
 *  TODO: documenter le format de ces données.
 */
function inc_campagnodon_connecteur_test_nouvelle_contribution_dist($mode_options, $params) {
  $id = sql_insertq('spip_campagnodon_testdata', [
    'idx' => $params['transaction_idx'] ?? null,
    'data' => json_encode($params)
  ]);
  if (!$id) {
    throw new Exception('Failed');
  }
  return $id;
}
