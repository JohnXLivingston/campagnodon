<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Cette fonction doit créer (le cas échéant) la transaction correspondante à une nouvelle échéance de don/adhésion récurrent.
 * Retourne `true` en cas de succès.
 * NB: si la transaction a déjà été créé, il faut également retourner `true` (il pourrait y avoir des appels concurrents).
 * 
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $parent_idx
 * La référence externe de la transaction parent
 * @param $idx
 * La référence externe de la transaction
 * @param $params
 *  Les données à envoyer.
 *  TODO: documenter le format de ces données.
 */
function inc_campagnodon_connecteur_test_garantir_echeance_existe_dist($mode_options, $parent_idx, $idx, $params) {
  $id = sql_insertq('spip_campagnodon_testdata', [
    'idx' => $idx,
    'data' => json_encode(array_merge(['parent_idx' => $parent_idx], $params))
  ]);
  if (!$id) {
    throw new Exception('Failed');
  }
  return true;
}
