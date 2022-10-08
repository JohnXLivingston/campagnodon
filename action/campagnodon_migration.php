<?php

/**
 * @plugin     Campagnodon
 * @copyright  2022
 * @author     John Livingston
 * @licence    AGPL-v3
 */


if (!defined('_ECRIRE_INC_VERSION')){
	return;
}

function campagnodon_migration_config($plugin_name, $type_transaction) {
	// NB: cette fonction est utilisée dans un filtre, pour tester l'affichage du bouton.
	if (!defined('_CAMPAGNODON_MIGRATION')) {
		return null;
	}
	if (!is_array(_CAMPAGNODON_MIGRATION)) {
		return null;
	}
	if (!array_key_exists($plugin_name, _CAMPAGNODON_MIGRATION) || !is_array(_CAMPAGNODON_MIGRATION[$plugin_name])) {
		return null;
	}
	if (!array_key_exists($type_transaction, _CAMPAGNODON_MIGRATION[$plugin_name]) || !is_array(_CAMPAGNODON_MIGRATION[$plugin_name][$type_transaction])) {
		return null;
	}
	$migration_config = _CAMPAGNODON_MIGRATION[$plugin_name][$type_transaction];
	if (empty($migration_config['mode'])) {
		spip_log('Configuration invalide pour la migration (mode manquant): "'.$$plugin_name .'/'. $type_transaction.'"', 'campagnodon'._LOG_ERREUR);
		return;
	}
	if (empty($migration_config['idx_format'])) {
		spip_log('Configuration invalide pour la migration (idx_format manquant): "'.$$plugin_name .'/'. $type_transaction.'"', 'campagnodon'._LOG_ERREUR);
		return;
	}
	return $migration_config;
}

function action_campagnodon_migration_dist(){
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	if ($arg !== 'souscription_don_recurrent') {
		spip_log('Argument invalide pour l\'action migration: "'.$arg.'"', 'campagnodon'._LOG_ERREUR);
		return;
	}

	$migration_config = campagnodon_migration_config('souscription', 'don_recurrent');
	if (!$migration_config) {
		spip_log('Configuration non trouvée pour la migration: "'.$arg.'"', 'campagnodon'._LOG_ERREUR);
		return;
	}

	include_spip('inc/campagnodon.utils');


	$select = "S.id_souscription, S.type_souscription,S.abo_statut,"
		. "T.date_paiement, T.mode, T.autorisation_id, T.id_transaction, T.pay_id,"
		. "S.date_souscription ";

	$from = " spip_souscriptions AS S "
		. " LEFT JOIN spip_souscriptions_liens AS L ON (L.id_souscription=S.id_souscription) "
		. " LEFT JOIN spip_transactions AS T ON (T.id_transaction=L.id_objet AND objet='transaction') ";
	// On joint à spip_campagnodon_transactions, pour ensuite pouvoir retirer ce qui a déjà été migré:
	$from.= " LEFT OUTER JOIN spip_campagnodon_transactions as CT ON (CT.id_transaction = T.id_transaction AND CT.mode = ".sql_quote($migration_config['mode']).") ";

	$where = " type_souscription = ".sql_quote('don')." ";
	$where.= " AND T.pay_id ";
	// abo_statut peut valoir: 'ok', 'resilie', 'command', 'non'. On prend juste ce qui est en cours (commande et ok)
	$where.= " AND (abo_statut = ".sql_quote('commande')." OR abo_statut = ".sql_quote('ok').") ";
	// on enlève ce qui a déjà été migré :
	$where.= " AND CT.id_campagnodon_transaction IS NULL ";

	// on trie par date_paiement, pour migrer dans l'ordre.
	$order.= " date_paiement ASC ";

	spip_log(
		'On va lancer une migration à partir de la requête sql: '
		. 'SELECT '.$select.' FROM '.$from. ' WHERE '.$where. 'ORDER BY'.$order,
		'campagnodon'._LOG_INFO
	);
	$res = sql_select($select, $from, $where, null, $order);
	while($row = sql_fetch($res)) {
		$idx = $migration_config['idx_format'];
		$idx = preg_replace('/\{ID_SOUSCRIPTION\}/', $row['id_souscription'], $idx);
		$idx = preg_replace('/\{ID_TRANSACTION\}/', $row['id_transaction'], $idx);
		$idx = preg_replace('/\{PAY_ID\}/', $row['pay_id'], $idx);
		spip_log('Il faut créer une migration avec idx="'.$idx.'".', 'campagnodon'._LOG_DEBUG);
	}
}
