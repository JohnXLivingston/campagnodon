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

function critere_campagnodon_jointure_transactions_dist($idb, &$boucles, $crit) {
  $boucle = &$boucles[$idb];
  $boucle->from['transactions'] = 'spip_transactions';
  $boucle->from_type['transactions'] = 'LEFT';
  // le format de join est :
  // array(table depart, cle depart [,cle arrivee[,condition optionnelle and ...]])
  $boucle->join['transactions'] = array("'campagnodon_transactions'", "'id_transaction'", "'id_transaction'");
  $boucle->select[] = 'transactions.mode AS transaction_mode'; // ce champ a un homonyme dans campagnodon_transactions, on contourne.
}

function balise_TRANSACTION_MODE_dist($p) {
	return rindex_pile($p, 'transaction_mode', 'campagnodon_jointure_transactions');
}
