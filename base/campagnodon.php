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

function campagnodon_use_test_table() {
  if (defined('_CAMPAGNODON_MODES') && is_array(_CAMPAGNODON_MODES)) {
    $tests = array_filter(_CAMPAGNODON_MODES, function ($c) {
      if (is_array($c) && $c['type'] === 'test') {
        return true;
      }
      return false;
    });
    if (count($tests) > 0) {
      return true;
    }
  }
  return false;
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
  $interfaces['table_des_tables']['campagnodon_transactions'] = 'campagnodon_transactions';
  $interfaces['table_des_tables']['campagnodon_campagnes'] = 'campagnodon_campagnes';
  if (campagnodon_use_test_table()) {
    $interfaces['table_des_tables']['campagnodon_testdata'] = 'campagnodon_testdata';
  }
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
  $tables['spip_campagnodon_transactions'] = [
    'principale' => 'oui',
    'page' => false,
    'type' => 'campagnodon_transactions',
    'date' => 'date_transaction',
    'field' => [
      'id_campagnodon_transaction' => 'bigint(21) NOT NULL',
      'id_transaction' => 'bigint(21) DEFAULT NULL', // Id de la transaction SPIP Bank
      'date_transaction' => 'datetime NOT NULL DEFAULT NOW()',
      'type_transaction' => "varchar(20) NOT NULL DEFAULT 'don'",
      'id_campagnodon_campagne' => 'bigint(21) DEFAULT NULL',
      'mode' => 'varchar(20) CHARACTER SET ASCII DEFAULT NULL', // Dans quel système externe est traité cette transaction. Cette valeur doit être l'une des clé de _CAMPAGNODON_MODES.
      'transaction_distant' => 'varchar(255) DEFAULT NULL', // L'ID de transaction qu'on renseigne dans CiviCRM. De la forme: campagnodon/123456789.
      'statut_synchronisation' => 'varchar(20) DEFAULT NULL', // Statut de la dernière synchronisation. 'ok', 'echec', 'attente', 'attente_rejoue'.
      'date_synchronisation' => 'datetime DEFAULT NULL', // Date de la dernière synchro (ou tentative de synchro).
      'maj' => 'TIMESTAMP'
    ],
    'key' => [
      'PRIMARY KEY' => 'id_campagnodon_transaction',
      'UNIQUE campagnodon_id_transaction' => 'id_transaction'
    ],
    'champs_editables' => [],
    'rechercher_champs' => [],
    'join' => [
      'id_transaction' => 'id_transaction',
      'id_campagnodon_campagne' => 'id_campagnodon_campagne'
    ],
    'tables_jointures' => [
      'id_transaction' => 'spip_transactions',
      'id_campagnodon_campagne' => 'spip_campagnodon_campagnes'
    ]
  ];
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
      'origine' => 'varchar(20) CHARACTER SET ASCII DEFAULT NULL', // origine de la campagne. Cette valeur doit être l'une des clé de _CAMPAGNODON_MODES.
      'id_origine' => 'bigint(21) DEFAULT NULL', // ID dans la base de donnée d'origine (le cas échéant).
      'statut' => 'varchar(20) CHARACTER SET ASCII NOT NULL DEFAULT "publie"',
      'maj' => 'TIMESTAMP'
    ],
    'key' => [
      'PRIMARY KEY' => 'id_campagnodon_campagne',
      'UNIQUE campagnodon_origine_key' => 'origine,id_origine'
    ],
    'champs_editables' => [],
    'rechercher_champs' => [],
  ];

  // On ajoute une table de test s'il y a au moins un connecteur de test configuré.
  if (campagnodon_use_test_table()) {
    $tables['spip_campagnodon_testdata'] = [
      'principale' => 'oui',
      'page' => false,
      'type' => 'campagnodon_testdata',
      'date' => 'date',
      'field' => [
        'id_campagnodon_testdata' => 'bigint(21) NOT NULL',
        'idx' => 'varchar(255) DEFAULT NULL',
        'statut' => "varchar(20) NOT NULL DEFAULT 'init'",
        'mode_paiement' => 'varchar(20) DEFAULT NULL',
        'data' => 'text',
        'date' => 'datetime NOT NULL DEFAULT NOW()',
        'maj' => 'TIMESTAMP'
      ],
      'key' => [
        'PRIMARY KEY' => 'id_campagnodon_testdata',
        'UNIQUE campagnodon_idx' => 'idx'
      ],
      'champs_editables' => [],
      'rechercher_champs' => [],
    ];
  }

  return $tables;
}
