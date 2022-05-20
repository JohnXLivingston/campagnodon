<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Retourne les infos de contact qui ont été soumise à la sauvegarde.
 * C'est utilisé pour le DSP2 de SPIP_BANK.
 * Doit, dans la mesure du possible, retourner nom, adresse, etc...
 * (voir le pipeline campagnodon_bank_dsp2_renseigner_facturation)
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $transaction_distant
 *  L'id dans le système distant.
 */
function inc_campagnodon_connecteur_test_dsp2_renseigner_facturation_dist($mode_options, $transaction_distant) {
  // On remonte les infos de la base de donnée (table testdata).
  if (empty($transaction_distant)) {
    return false;
  }
  $data = sql_getfetsel('data', 'spip_campagnodon_testdata', 'idx=' . sql_quote($transaction_distant));
  if (!$data) {
    return false;
  }

  $data = json_decode($data, true);
  $result = array(
    'nom' => $data['last_name'] ?? null,
    'prenom' => $data['first_name'] ?? null,
    'email' => $data['email'] ?? null,
    'adresse' => $data['street_address'] ?? null,
    'code_postal' => $data['postal_code'] ?? null,
    'ville' => $data['city'] ?? null,
    'pays' => $data['country'] ?? null,
  );
  spip_log('inc_campagnodon_connecteur_test_dsp2_renseigner_facturation_dist: données remontées: '.json_encode($result).'.', 'campagnodon'._LOG_DEBUG);
  return $result;
}
