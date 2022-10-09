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

	$plugin_name = 'souscription';
	$migration_config = campagnodon_migration_config($plugin_name, 'don_recurrent');
	if (!$migration_config) {
		spip_log('Configuration non trouvée pour la migration: "'.$arg.'"', 'campagnodon'._LOG_ERREUR);
		return;
	}

	include_spip('inc/campagnodon.utils');


	// $select = "S.id_souscription, S.abo_statut,"
	// 	. "T.id_transaction, T.pay_id, T.transaction_hash, T.date_transaction ";

	// $from = " spip_souscriptions AS S "
	// 	. " LEFT JOIN spip_souscriptions_liens AS L ON (L.id_souscription=S.id_souscription) "
	// 	. " LEFT JOIN spip_transactions AS T ON (T.id_transaction=L.id_objet AND objet='transaction') ";
	// // On joint à spip_campagnodon_transactions, pour ensuite pouvoir retirer ce qui a déjà été migré:
	// $from.= " LEFT OUTER JOIN spip_campagnodon_transactions as CT ON (CT.id_transaction = T.id_transaction AND CT.mode = ".sql_quote($migration_config['mode']).") ";

	// $where = " type_souscription = ".sql_quote('don')." ";
	// $where.= " AND T.pay_id ";
	// // abo_statut peut valoir: 'ok', 'resilie', 'command', 'non'. On prend juste ce qui est en cours (commande et ok)
	// $where.= " AND (abo_statut = ".sql_quote('commande')." OR abo_statut = ".sql_quote('ok').") ";
	// // on enlève ce qui a déjà été migré :
	// $where.= " AND CT.id_campagnodon_transaction IS NULL ";

	// // on trie par date_paiement, pour migrer dans l'ordre.
	// $order.= " date_paiement ASC ";

	// On va remonter toutes les souscriptions en cours (abo_statut sert à la fois à trouver les paiements récurrents et ceux en cours)
	$select = 'id_souscription';
	$from = 'spip_souscriptions';
	$where = " type_souscription = ".sql_quote('don')." ";
	// abo_statut peut valoir: 'ok', 'resilie', 'command', 'non'. On prend juste ce qui est en cours (commande et ok)
	$where.= " AND (abo_statut = ".sql_quote('commande')." OR abo_statut = ".sql_quote('ok').") ";
	$order = 'id_souscription ASC';

	// spip_log(
	// 	'On va lancer une migration à partir de la requête sql: '
	// 	. 'SELECT '.$select.' FROM '.$from. ' WHERE '.$where. ' ORDER BY '.$order,
	// 	'campagnodon'._LOG_INFO
	// );
	$res = sql_select($select, $from, $where, null, $order);

	$cpt_souscription = 0;
	$cpt_transaction = 0;
	$cpt_transaction_deja_migre = 0;
	$cpt_transaction_migree = 0;
	while($souscription = sql_fetch($res)) {
		$cpt_souscription++;

		// Il faut maintenant chercher pour cette souscription toutes les transactions.
		// On va les remonter dans l'ordre.
		// On va migrer toutes celles qui ne l'ont pas encore été
		// (de sorte qu'on puisse relancer ce script plusieurs fois sans risque).
		// La première sera la transaction campagnodon parent.
		$res_transactions = sql_select(
			'id_objet',
			'spip_souscriptions_liens',
			'id_souscription = '.sql_quote($souscription['id_souscription']),
			null,
			'id_objet ASC'
		);
		$id_campagnodon_transaction_parent = null;
		while($id_transaction_ligne = sql_fetch($res_transactions)) {
			$cpt_transaction++;
			$id_transaction = $id_transaction_ligne['id_objet'];
			$transaction = sql_fetsel(
				'*',
				'spip_transactions',
				'id_transaction = '.sql_quote($id_transaction)
			);
			if (!$transaction) {
				spip_log("Impossible de trouver la transaction id_transaction=".$id_transaction, "campagnodon"._LOG_ERREUR);
				continue;
			}

			// on cherche si déjà migrée...
			$campagnodon_transaction = sql_fetsel(
				'*',
				'spip_campagnodon_transactions',
				'id_transaction = '.sql_quote($id_transaction)
			);
			if ($campagnodon_transaction) {
				$cpt_transaction_deja_migre++;
				// Si c'est la première, on a notre parent !
				if (!$id_campagnodon_transaction_parent) {
					$id_campagnodon_transaction_parent = $campagnodon_transaction['id_campagnodon_transaction'];
				}
				continue;
			}


			$idx = $migration_config['idx_format'];
			$idx = preg_replace('/\{ID_SOUSCRIPTION\}/', $souscription['id_souscription'], $idx);
			$idx = preg_replace('/\{ID_TRANSACTION\}/', $transaction['id_transaction'], $idx);
			$idx = preg_replace('/\{PAY_ID\}/', $transaction['pay_id'], $idx);
			// spip_log('Il faut créer une migration avec idx="'.$idx.'".', 'campagnodon'._LOG_DEBUG);

			$est_echeance = !empty($id_campagnodon_transaction_parent);

			$insert = array(
				'id_campagnodon_campagne' => null, // FIXME: remonter la campagne depuis le système distant ?
				'mode' => $migration_config['mode'],
				'id_campagnodon_transaction_parent' => $id_campagnodon_transaction_parent,
				'transaction_distant' => $idx,
				'id_transaction' => $transaction['id_transaction'],
				'migre_de' => $plugin_name,
				'statut_migration_distant' => 'attente',
				'date_transaction' => $transaction['date_transaction']
			);
			if ($est_echeance) {
				$insert['type_transaction'] = 'don_mensuel_echeance';
			} else {
				$insert['type_transaction'] = 'don_mensuel';
				$insert['statut_recurrence'] = $souscription['abo_statut'] === 'commande' ? 'attente' : 'encours';
			}


			// spip_log(json_encode($insert), 'campagnodon'._LOG_DEBUG);

			$id_campagnodon_transaction = sql_insertq('spip_campagnodon_transactions', $insert);
			if (!($id_campagnodon_transaction > 0)) {
				spip_log("Erreur à la création de la transaction campagnodon idx='".$idx."'", "campagnodon"._LOG_ERREUR);
				continue;
			}
			$cpt_transaction_migree++;
			if (!$id_campagnodon_transaction_parent) {
				// C'est la première... c'est le parent
				$id_campagnodon_transaction_parent = $id_campagnodon_transaction;
				campagnodon_queue_synchronisation($id_campagnodon_transaction);
			} else {
				// il faut synchroniser les enfants un peu plus tard, pour être sûr que tout les parents ont été synchronisés.
				campagnodon_queue_synchronisation($id_campagnodon_transaction, 0, 5*60);
			}

			// Je dois aussi changer le parrain/tracking_id sur la transaction.
			if (false === sql_updateq(
				'spip_transactions',
				[
					'parrain' => 'campagnodon',
					'tracking_id' => $id_campagnodon_transaction
				],
				'id_transaction='.sql_quote($id_transaction)
			)) {
				spip_log("Erreur à la modification du parrain/tracking_id pour id_transaction=".$id_transaction, 'campagnodon'._LOG_ERREUR);
			}

			// $url_paiement = generer_url_public('campagnodon-payer', array('id_transaction'=>$transaction['id_transaction'], 'transaction_hash'=>$transaction['transaction_hash']), false, false);
			// $url_transaction = generer_url_ecrire('campagnodon_transaction', 'id_campagnodon_transaction='.htmlspecialchars($id_campagnodon_transaction), false, false);
		}
	}

	spip_log('La migration a parcouru '.$cpt_souscription.' souscriptions.', 'campagnodon'._LOG_INFO);
	spip_log('La migration a parcouru '.$cpt_transaction.' transactions.', 'campagnodon'._LOG_INFO);
	spip_log($cpt_transaction_deja_migre.' transactions étaient déjà migrées.', 'campagnodon'._LOG_INFO);
	spip_log($cpt_transaction_migree.' transactions ont été migrées.', 'campagnodon'._LOG_INFO);
}
