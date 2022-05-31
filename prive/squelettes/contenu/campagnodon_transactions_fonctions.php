<?php
/*
 * Auteurs :
 * John Livingston
 * (c) 2022 - AGPL-v3
 *
 */

if (!defined('_ECRIRE_INC_VERSION')){
	return;
}

function campagnodon_transactions_statuts_synchronisation(){
  $statuts = sql_allfetsel("statut_synchronisation, count(*) as n", "spip_campagnodon_transactions", "", "statut_synchronisation");
  $result = [
    '' => 0,
    'ok' => 0,
    // 'jamais' => 0, // cas rare, ne sera présent que s'il y a des lignes dans cet état
    'attente' => 0,
    // 'attente_rejoue' => 0, // cas rare, ne sera présent que s'il y a des lignes dans cet état
    'echec' => 0
  ];
  if ($statuts){
    foreach ($statuts as $line) {
      if (!array_key_exists($line['statut_synchronisation'], $line)) {
        $result[$line['statut_synchronisation']] = 0;
      }
      $result[$line['statut_synchronisation']]+= $line['n'];
    }
	}
  $result[''] = array_sum($result);
  return $result;
}
