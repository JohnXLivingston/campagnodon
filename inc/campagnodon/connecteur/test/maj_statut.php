<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Met à jour le statut dans le système distant.
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $idx
 * La référence externe de la transaction
 * @param $statut
 * Le nouveau statut. Doit être une valeur valide parmis: attente, ok, echec, abandon, rembourse
 * TODO: faut-il traiter "commande" ?
 * @param $mode_paiement
 * Le mode de paiement. Les valeurs peuvent etre assez diverses (cheque, payzen, ...)
 * @param $statut_recurrence
 * Le statut du don récurrent, le cas échéant. Cette info est sur la transaction initiale.
 */
function inc_campagnodon_connecteur_test_maj_statut_dist($mode_options, $idx, $statut, $mode_paiement, $statut_recurrence) {
  $r = sql_updateq('spip_campagnodon_testdata', [
    'statut' => $statut,
    'statut_recurrence' => $statut_recurrence,
    'mode_paiement' => $mode_paiement
  ], 'idx='.sql_quote($idx));

  if (false === $r) {
    return false;
  }
  return array(
    'statut' => $statut,
    'statut_recurrence' => $statut_recurrence
  );
}
