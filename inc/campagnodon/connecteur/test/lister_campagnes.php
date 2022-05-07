<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Remonte toutes les campagnes du système distant, pour les synchroniser en local.
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 */
function inc_campagnodon_connecteur_test_lister_campagnes_dist($mode_options) {
  // ici on retourne des données de test.
  return [
    [
      'titre' => 'Campagne fictive',
      'texte' => 'Ceci est une donnée de test',
      'id_origine' => '123456789',
      'date' => '2022-01-01',
      'statut' => 'publie'
    ]
  ];
}
