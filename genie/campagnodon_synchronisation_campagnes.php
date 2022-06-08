<?php

/**
 * Synchronise les campagnes depuis le(s) système(s) distant(s) (le cas échéant).
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
  include_spip('inc/campagnodon.utils');
  if (!defined('_CAMPAGNODON_MODES') || !is_array(_CAMPAGNODON_MODES)) {
    spip_log('Campagnodon: pas de mode configuré, on ne synchronise rien.', 'campagnodon'._LOG_DEBUG);
    return null;
  }
  $return = null;
  foreach (_CAMPAGNODON_MODES as $mode => $mode_options) {
    $fonction_lister_campagnes = campagnodon_fonction_connecteur($mode, 'lister_campagnes');
    if (!$fonction_lister_campagnes) {
      spip_log('Campagnodon: le mode '.$mode.' n\'a pas de connecteur lister_campagnes, on ne synchronise rien.', 'campagnodon'._LOG_DEBUG);
      continue;
    }
    spip_log('Campagnodon: le mode '.$mode.' a bien un connecteur lister_campagnes, on synchronise...', 'campagnodon'._LOG_DEBUG);

    $return = 1;

    $lignes_distantes = $fonction_lister_campagnes($mode_options);
    if (false === $lignes_distantes) {
      spip_log("Erreur à la remontée des campagnes distantes pour le mode ".$mode.", je ne synchronise rien ce coup ci.", "campagnodon"._LOG_ERREUR);
      continue;
    }

    /* Remontée des données depuis la base locale */
    $campagnes_par_id_origine = array();
    $sql_result = sql_select('*', 'spip_campagnodon_campagnes', array("origine = ".sql_quote($mode)));
    while ($row=sql_fetch($sql_result)) {
      $campagnes_par_id_origine[$row['id_origine']] = $row;
    }
    sql_free($sql_result);
    spip_log('Nombre de lignes récupérées sur la base locale pour le mode '.$mode.': '.count($campagnes_par_id_origine), 'campagnodon'._LOG_DEBUG);

    /* Création et mise à jour des lignes. */
    $id_origine_vues = array();
    foreach ($lignes_distantes as $ligne_distante) {
      $id_origine = $ligne_distante['id_origine'];
      $id_origine_vues[$id_origine] = true;

      $ligne_distante['origine'] = $mode;
      if (!array_key_exists($id_origine, $campagnes_par_id_origine)) {
        spip_log('Import de la campagne '.$mode.'/'.$id_origine, 'campagnodon'._LOG_DEBUG);
        sql_insertq('spip_campagnodon_campagnes', $ligne_distante);
      } else {
        spip_log('Campagne '.$mode.'/'.$id_origine.' déjà présente, mise à jour.', 'campagnodon'._LOG_DEBUG);
        $ligne_locale = $campagnes_par_id_origine[$id_origine];
        sql_updateq(
          'spip_campagnodon_campagnes',
          array(
            'titre' => $ligne_distante['titre'],
            'texte' => $ligne_distante['texte'],
            'date' => $ligne_distante['date'],
            'statut' => $ligne_distante['statut']
          ),
          'id_campagnodon_campagne='.sql_quote($ligne_locale['id_campagnodon_campagne'])
        );
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
      spip_log('La campagne '.$mode.'/'.$id_origine.' n\'est plus là, passage au statut=poubelle', 'campagnodon'._LOG_DEBUG);
      sql_updateq('spip_campagnodon_campagnes', array('statut' => 'poubelle'), 'id_campagnodon_campagne='.sql_quote($ligne_locale['id_campagnodon_campagne']));
    }
  }


  return $return;
}
