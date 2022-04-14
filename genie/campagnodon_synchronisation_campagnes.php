<?php

/**
 * Synchronise les campagnes depuis le système distant (le cas échéant).
 * 
 * @plugin Campagnodon
 * @copyright 2022
 * @author John Livingston
 * @licence AGPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


function genie_campagnodon_synchronisation_campagnes_dist() {
  if (_CAMPAGNODON_MODE !== 'civicrm') {
    spip_log('Campagnodon: pas de mode configuré, on ne synchronise rien.', 'campagnodon'._LOG_DEBUG);
    return null;
  }
  spip_log('Campagnodon: synchronisation des campagnes depuis CiviCRM...', 'campagnodon'._LOG_DEBUG);
  /* Remontée des données de CiviCRM */
  include_spip('inc/civicrm/class.api');
  $civi_api = new civicrm_api3(_CAMPAGNODON_CIVICRM_API_OPTIONS);
  $result = $civi_api->Campaign->get([
    // FIXME: faut-il une pagination plus fine ?
    'options' => array('limit' => 10000)
  ]);
  // spip_log('Résultat CiviCRM->Campaign->get: ' . json_encode($civi_api->lastResult), 'campagnodon'._LOG_DEBUG);

  if (!$result) {
    spip_log("Erreur CiviCRM->Campaign->get " . $civi_api->errorMsg(), "campagnodon"._LOG_ERREUR);
    return 1;
  }

  $lignes_distantes = $civi_api->values;
  if (empty($ligne_distantes)) {
    $ligne_distantes = [];
  }
  spip_log('Nombre de lignes récupérées sur CiviCRM: '.count($lignes_distantes), 'campagnodon'._LOG_DEBUG);

  /* Remontée des données depuis la base locale */
  $campagnes_par_id_origine = array();
  $sql_result = sql_select('*', 'spip_campagnodon_campagnes', array("origine = 'civicrm'"));
  while ($row=sql_fetch($sql_result)) {
    $campagnes_par_id_origine[$row['id_origine']] = $row;
  }
  sql_free($sql_result);
  spip_log('Nombre de lignes récupérées sur la base locale: '.count($campagnes_par_id_origine), 'campagnodon'._LOG_DEBUG);

  /* Création et mise à jour des lignes. */
  $id_origine_vues = array();
  foreach ($lignes_distantes as $key => $ligne_distante) {
    $id_origine = $ligne_distante->id;
    $id_origine_vues[$id_origine] = true;
    if (!array_key_exists($id_origine, $campagnes_par_id_origine)) {
      spip_log('Import de la campagne civicrm/'.$id_origine, 'campagnodon'._LOG_DEBUG);
      sql_insertq('spip_campagnodon_campagnes', array(
        'titre' => $ligne_distante->name,
        'texte' => $ligne_distante->title,
        'origine' => 'civicrm',
        'id_origine' => $id_origine,
        'date' => $ligne_distante->start_date,
        'statut' => 'publie' // TODO: Reprendre le statut de CiviCRM.
      ));
    } else {
      spip_log('Campagne civicrm/'.$id_origine.' déjà présente.', 'campagnodon'._LOG_DEBUG);
      // TODO: mettre à jour la campagne.
    }
  }

  /* Passage en statut=poubelle des campagnes qui ont disparu coté civiCRM */
  foreach ($campagnes_par_id_origine as $id_origine => $ligne_locale) {
    if (array_key_exists($id_origine, $id_origine_vues)) {
      continue;
    }
    if ($ligne_locale['statut'] === 'poubelle') {
      continue;
    }
    spip_log('La campagne civicrm/'.$id_origine.' n\'est plus là, passage au statut=poubelle', 'campagnodon'._LOG_DEBUG);
    sql_updateq('spip_campagnodon_campagnes', array('statut' => 'poubelle'), 'id_campagnodon_campagne='.sql_quote($ligne_locale['id_campagnodon_campagne']));
  }

  return 1;
}
