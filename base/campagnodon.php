<?php

/**
 * Déclarations relatives à la base de données
 *
 * @plugin     Campagnodon
 * @copyright  2022
 * @author     John Livingston
 * @licence    AGPLv3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclaration des alias de tables pour SPIP (permet notamment de faire des BOUCLE_nom_de_lobjet).
 * 
 * @pipeline declarer_tables_interfaces
 * @param array $interfaces
 *     Déclarations d'interface pour le compilateur
 * @return array
 *     Déclarations d'interface pour le compilateur
 */
function campagnodon_declarer_tables_interfaces($interfaces) {
  $interfaces['table_des_tables']['campagnodon_campagnes'] = 'campagnodon_campagnes';
  return $interfaces;
}

/**
 * Déclaration des objets éditoriaux
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function campagnodon_declarer_tables_objets_sql($tables) {
  $tables['spip_campagnodon_campagnes'] = [
    'principale' => 'oui',
    'page' => false,
    'type' => 'campagnodon_campagnes',
    'titre' => "titre AS titre, '' AS lang",
    'date' => 'date',
    'field' => [
      'id_campagnodon_campagne' => 'bigint(21) NOT NULL',
      'titre' => "text NOT NULL DEFAULT ''",
      'texte' => "longtext NOT NULL DEFAULT ''",
      'date' => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
      'origine' => 'varchar(20) CHARACTER SET ASCII DEFAULT NULL', // origine de la campagne. Valeurs possibles: «civicrm».
      'id_origine' => 'bigint(21) DEFAULT NULL', // ID dans la base de donnée d'origine (le cas échéant).
      'statut' => 'varchar(20) CHARACTER SET ASCII NOT NULL DEFAULT "publie"',
      'maj' => 'TIMESTAMP'
    ],
    'key' => [
      'PRIMARY KEY' => 'id_campagnodon_campagne',
      'UNIQUE origine_key' => 'origine,id_origine'
    ],
    'champs_editables' => [],
    'rechercher_champs' => [],
  ];
  return $tables;
}
