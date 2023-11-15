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
 * @param $mode_paiement_distant
 * Le mode de paiement. Les valeurs peuvent etre assez diverses (cheque, payzen, ...)
 * @param $statut_recurrence
 * Le statut du don récurrent, le cas échéant. Cette info est sur la transaction initiale.
 */
function inc_campagnodon_connecteur_civicrm_maj_statut_dist($mode_options, $idx, $statut, $mode_paiement_distant, $statut_recurrence) {
  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new campagnodon_civicrm_api3($mode_options['api_options']);

  $statut_distant = null;
  switch($statut) {
    case 'ok': $statut_distant = 'completed'; break;
    case 'echec': $statut_distant = 'failed'; break;
    case 'attente': $statut_distant = 'pending'; break;
    case 'abandon': $statut_distant = 'cancelled'; break;
    case 'rembourse': $statut_distant = 'refunded'; break;
    default:
      throw new Exception('Je ne sais pas traduire le statut '.$statut);
  }

  $statut_recurrence_distant = null;
  if ($statut_recurrence) {
    switch($statut_recurrence) {
      case 'initialisation': $statut_recurrence_distant = 'init'; break;
      case 'attente': $statut_recurrence_distant = 'waiting'; break;
      case 'encours': $statut_recurrence_distant = 'ongoing'; break;
      case 'termine': $statut_recurrence_distant = 'ended'; break;
      default:
        throw new Exception('Je ne sais pas traduire le statut de récurrence '.$statut_recurrence);
    }
  }

  $result = $civi_api->Campagnodon->updatestatus(array(
    'transaction_idx' => $idx,
    'status' => $statut_distant,
    'recurring_status' => $statut_recurrence_distant,
    'payment_instrument' => $mode_paiement_distant
  ));
  if (!$result) {
    throw new Exception("Erreur CiviCRM " . $civi_api->errorMsg());
  }

  $civicrm_result = $civi_api->values;
  $line = array_pop($civicrm_result);
  if (!$line) {
    throw new Exception("Erreur CiviCRM, je ne trouve pas de ligne dans le résultat.");
  }
  return array(
    'statut' => $line->status,
    'statut_recurrence' => property_exists($line, 'recurring_status') ? $line->recurring_status : null
  );
}
