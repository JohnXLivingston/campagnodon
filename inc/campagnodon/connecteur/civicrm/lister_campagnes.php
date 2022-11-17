<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Remonte toutes les campagnes du système distant, pour les synchroniser en local.
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 */
function inc_campagnodon_connecteur_civicrm_lister_campagnes_dist($mode_options) {
  spip_log('Campagnodon: synchronisation des campagnes depuis CiviCRM...', 'campagnodon'._LOG_DEBUG);
  /* Remontée des données de CiviCRM */
  include_spip('inc/campagnodon/connecteur/civicrm/class.api');
  $civi_api = new campagnodon_civicrm_api3($mode_options['api_options']);
  $result = $civi_api->Campagnodon->campaign([
    // FIXME: faut-il une pagination plus fine ?
    'options' => array('limit' => 10000)
  ]);
  // spip_log('Résultat CiviCRM->Campagnodon->campaign: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

  if (!$result) {
    spip_log("Erreur CiviCRM->Campagnodon->campaign: " . $civi_api->errorMsg(), "campagnodon"._LOG_ERREUR);
    return false;
  }

  $lignes_distantes = $civi_api->values;
  if (empty($ligne_distantes)) {
    $ligne_distantes = [];
  }
  spip_log('Nombre de lignes récupérées sur CiviCRM: '.count($lignes_distantes), 'campagnodon'._LOG_DEBUG);

  $result = [];
  foreach ($lignes_distantes as $key => $ligne_distante) {
    array_push($result, array(
      'titre' => property_exists($ligne_distante, 'title') ? $ligne_distante->title : null,
      'texte' => property_exists($ligne_distante, 'description') ? $ligne_distante->description : null,
      'id_origine' => property_exists($ligne_distante, 'id') ? $ligne_distante->id : null,
      'date' => property_exists($ligne_distante, 'start_date') ? $ligne_distante->start_date : null,
      'objectif' => property_exists($ligne_distante, 'goal_revenue') ? $ligne_distante->goal_revenue : null,
      'recolte' => property_exists($ligne_distante, 'current_revenue') ? $ligne_distante->current_revenue : null,
      'statut' => 'publie' // TODO: Reprendre le statut de CiviCRM.
    ));
  }
  return $result;
}
