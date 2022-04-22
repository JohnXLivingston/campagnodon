<?php

/**
 * Cette fonction retourne l'identifiant à placer dans le champs transaction_idx de CiviCRM.
 */
function get_transaction_idx_distant($id_campagnodon_transaction) {
  $prefix = !empty(_CAMPAGNODON_CIVICRM_PREFIX) ? _CAMPAGNODON_CIVICRM_PREFIX : 'campagnodon';
  return $prefix . '/' . $id_campagnodon_transaction;
}
