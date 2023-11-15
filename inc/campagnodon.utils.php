<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function _idx_format_id($mode_options, $id) {
	if (
		is_array($mode_options)
		&& array_key_exists('idx_id_length', $mode_options)
		&& is_numeric($mode_options['idx_id_length'])
	) {
		return substr(
			str_repeat('0', $mode_options['idx_id_length']) . $id,
			0 - $mode_options['idx_id_length'],
			$mode_options['idx_id_length']
		);
	} else {
		return $id;
	}
}

/**
 * Cette fonction retourne l'identifiant à placer dans le champs transaction_idx de CiviCRM.
 */
function get_transaction_idx_distant($mode_options, $id_campagnodon_transaction, $id_campagnodon_transaction_parent = null) {
	if (
		is_array($mode_options)
		&& array_key_exists('prefix', $mode_options)
		&& is_string($mode_options['prefix'])
	) {
			$prefix = $mode_options['prefix'];
	} else {
		$prefix = 'campagnodon';
	}
	if ($id_campagnodon_transaction_parent) {
		$prefix.= '/' . _idx_format_id($mode_options, $id_campagnodon_transaction_parent);
	}
	return $prefix . '/' . _idx_format_id($mode_options, $id_campagnodon_transaction);
}

/**
 * Retourne, le cas échéant, la fonction de connecteur à utiliser.
 * @param string $mode
 *  Le mode utilisé (la clé dans _CAMPAGNODON_MODES).
 * @param string $nom_fonction
 *  Le nom de la fonction connecteur souhaitée.
 */
function campagnodon_fonction_connecteur($mode, $nom_fonction) {
	if (!defined('_CAMPAGNODON_MODES') || !is_array(_CAMPAGNODON_MODES)) {
		return false;
	}
	if (!array_key_exists($mode, _CAMPAGNODON_MODES)) {
		return false;
	}
	$type = _CAMPAGNODON_MODES[$mode]['type'];
	if (!preg_match('/^[a-z]+$/', $type)) {
		return false;
	}
	if (!preg_match('/^[a-z0-9_]+$/', $nom_fonction)) {
		return false;
	}
	return charger_fonction($nom_fonction, 'inc/campagnodon/connecteur/'.$type, true);
}

function campagnodon_mode_options($mode) {
	if (!defined('_CAMPAGNODON_MODES') || !is_array(_CAMPAGNODON_MODES)) {
		return array();
	}
	if (!array_key_exists($mode, _CAMPAGNODON_MODES)) {
		return array();
	}
	return _CAMPAGNODON_MODES[$mode];
}

function _traduit_type_paiement($mode_options, $type, $refcb) {
	if (
		is_array($mode_options)
		&& array_key_exists('type_paiement', $mode_options)
		&& is_array($mode_options['type_paiement'])
	) {
		if (strncmp($refcb, 'SEPA', 4)===0) {
			if (array_key_exists('sepa_'.$type, $mode_options['type_paiement'])) {
				return $mode_options['type_paiement']['sepa_'.$type];
			}
		}
		if (array_key_exists($type, $mode_options['type_paiement'])) {
			return $mode_options['type_paiement'][$type];
		}
	}
	return $type;
}

function campagnodon_maj_sync_statut($id_campagnodon_transaction, $statut_synchronisation, $statut_distant = null, $statut_recurrence_distant = null) {
	$params = [
		'statut_synchronisation' => sql_quote($statut_synchronisation),
		'date_synchronisation' => 'NOW()'
	];
	if (null !== $statut_distant) {
		$params['statut_distant'] = sql_quote($statut_distant);
	}
	if (null !== $statut_recurrence_distant) {
		$params['statut_recurrence_distant'] = sql_quote($statut_recurrence_distant);
	}
	sql_update('spip_campagnodon_transactions', $params, 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
}

function campagnodon_queue_synchronisation($id_campagnodon_transaction, $nb_tentatives = 0, $delais_additionnel = 0) {
	$id_job = null;
	if ($nb_tentatives > 10) {
		spip_log("J'ai dépassé le nombre de tentatives max pour spip_campagnodon_transactions=".$id_campagnodon_transaction.', je ne replanifie rien.', 'campagnodon'._LOG_ERREUR);
		campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
		return;
	}
	if ($nb_tentatives > 0) {
		spip_log('La synchronisation ayant échoué pour spip_campagnodon_transactions='.$id_campagnodon_transaction.', je replanifie une synchro (tentative='.$nb_tentatives.').', 'campagnodon'._LOG_ERREUR);
		campagnodon_maj_sync_statut($id_campagnodon_transaction, 'attente_rejoue');
		$id_job = job_queue_add(
			'campagnodon_synchroniser_transaction',
			'Campagnodon - Synchronisation de la transaction '.$id_campagnodon_transaction. ' (tentative n°'.$nb_tentatives.' après échec)',
			[$id_campagnodon_transaction, $nb_tentatives],
			'inc/campagnodon.utils',
			false, // on autorise la création, de tâches duplicate. Vaut mieux synchroniser plus que nécessaire, pour éviter les effets de bords.
			time() + ($nb_tentatives * 120),
			-10
		);
	} else {
		campagnodon_maj_sync_statut($id_campagnodon_transaction, 'attente');
		$id_job = job_queue_add(
			'campagnodon_synchroniser_transaction',
			'Campagnodon - Synchronisation de la transaction '.$id_campagnodon_transaction,
			[$id_campagnodon_transaction],
			'inc/campagnodon.utils',
			false, // on autorise la création, de tâches duplicate. Vaut mieux synchroniser plus que nécessaire, pour éviter les effets de bords.
			$delais_additionnel ? (time() + $delais_additionnel) : 0 // on execute tout de suite, ou après le délais précisé en option.
		);
	}

	if ($id_job) {
		job_queue_link($id_job, ['objet' => 'campagnodon_transactions', 'id_objet' => $id_campagnodon_transaction]);
	}
}


function campagnodon_traduit_financial_type($mode_options, $type) {
	if (
		is_array($mode_options)
		&& array_key_exists('type_contribution', $mode_options)
		&& is_array($mode_options['type_contribution'])
		&& array_key_exists($type, $mode_options['type_contribution'])
	) {
		return $mode_options['type_contribution'][$type];
	}
	return $type;
}

/**
 * Synchronise une transaction avec le système distant.
 * En cas d'échec (système injoignable par ex), replanifie une synchronisation.
 * @param $id_campagnodon_transaction
 */
function campagnodon_synchroniser_transaction($id_campagnodon_transaction, $nb_tentatives = 0) {
	spip_log(__FUNCTION__.' Appel id_campagnodon_transaction='.$id_campagnodon_transaction.', tentative #'.$nb_tentatives.'.', 'campagnodon'._LOG_DEBUG);

	$campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
	if (!$campagnodon_transaction) {
		spip_log(__FUNCTION__.' spip_campagnodon_transactions introuvable: '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		return 0;
	}
	$transaction = sql_fetsel('*', 'spip_transactions', 'id_transaction=' . intval($campagnodon_transaction['id_transaction']));
	if (!$transaction) {
		spip_log(__FUNCTION__.' Transaction introuvable pour spip_campagnodon_transactions='.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
		return 0;
	}

	include_spip('inc/campagnodon/form/utils');
	include_spip('inc/campagnodon/form/init');

	$mode = $campagnodon_transaction['mode'];
	$mode_options = campagnodon_mode_options($mode);

	// Si c'est une ligne migree, on commence par la migrer si nécessaire.
	if (!empty($campagnodon_transaction['migre_de']) && $campagnodon_transaction['statut_migration_distant'] === 'attente') {
		// On doit tenter de migrer la ligne.
		spip_log(
			__FUNCTION__.' La transaction campagnodon '.$id_campagnodon_transaction
			.' est migree d\'un ancien plugin, et n\'a pas encore été synchronisée avec le système distant.'
			.' Je dois appeler le connecteur migrer_contribution.',
			'campagnodon'._LOG_DEBUG
		);
		$fonction_migrer_contribution = campagnodon_fonction_connecteur($mode, 'migrer_contribution');
		if (!$fonction_migrer_contribution) {
			spip_log(__FUNCTION__.' Campagnodon mal configuré, impossible de trouver le connecteur migrer_contribution pour le mode: '.$mode, 'campagnodon'._LOG_ERREUR);
			campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
			return 0;
		}

		$campagnodon_transaction_parent = null;
		if (!empty($campagnodon_transaction['id_campagnodon_transaction_parent'])) {
			$campagnodon_transaction_parent = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($campagnodon_transaction['id_campagnodon_transaction_parent']));
			if (!$campagnodon_transaction_parent) {
				spip_log(__FUNCTION__.' spip_campagnodon_transactions parent introuvable: '.$campagnodon_transaction['id_campagnodon_transaction_parent'], 'campagnodon'._LOG_ERREUR);
				campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
				return 0;
			}
		}

		$distant_operation_type = form_utils_operation_type_distant($campagnodon_transaction['type_transaction']);

		$url_transaction = generer_url_ecrire('campagnodon_transaction', 'id_campagnodon_transaction='.htmlspecialchars($id_campagnodon_transaction), false, false);
		$params_migrer_contribution = [
			'trxn_id' => $campagnodon_transaction['migre_cle'],
			'transaction_idx' => $campagnodon_transaction['transaction_distant'],
			'parent_transaction_idx' => $campagnodon_transaction_parent ? $campagnodon_transaction_parent['transaction_distant'] : null,
			'start_date' => $campagnodon_transaction['date_transaction'],
			// 'payment_url' => $url_paiement, FIXME?
			'transaction_url' => $url_transaction,
			'operation_type' => $distant_operation_type
		];

		try {
			$resultat = $fonction_migrer_contribution($mode_options, $params_migrer_contribution);
			if (false === $resultat) {
				spip_log(
					__FUNCTION__." Il semblerait que l\'appel au connecteur migrer_contribution a échoué pour spip_campagnodon_transactions=".$id_campagnodon_transaction
					. ', il faut reprogrammer la synchronisation. ',
					'campagnodon'._LOG_ERREUR
				);
				campagnodon_queue_synchronisation($id_campagnodon_transaction, $nb_tentatives + 1);
				return 0;
			}

			// TODO: sur quel critere mettre un statut_migration_distant à 'ko' ?
			if (false === sql_updateq(
				'spip_campagnodon_transactions',
				[
					'statut_migration_distant' => 'ok',
				],
				'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
			)) {
				spip_log('Erreur à la modification de la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
				campagnodon_queue_synchronisation($id_campagnodon_transaction, $nb_tentatives + 1);
				return 0;
			}
		} catch (Exception $e) {
			spip_log(
				__FUNCTION__
				. ' Il semblerait que la migrer_contribution ait échoué pour spip_campagnodon_transactions='.$id_campagnodon_transaction
				. " L\'erreur: ".$e->getMessage(),
				'campagnodon'._LOG_ERREUR
			);
			campagnodon_queue_synchronisation($id_campagnodon_transaction, $nb_tentatives + 1);
			return 0;
		}

		// Je recharge la ligne
		$campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
		if (!$campagnodon_transaction) {
			spip_log(__FUNCTION__.' spip_campagnodon_transactions introuvable (après avoir migré): '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
			campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
			return 0;
		}
	}

	// Si on n'a pas réussi à migrer... on laisse tomber.
	if (!empty($campagnodon_transaction['migre_de']) && $campagnodon_transaction['statut_migration_distant'] !== 'ok') {
		spip_log(__FUNCTION__.' Je ne peux synchro '.$id_campagnodon_transaction.' car le statut de migration est: '.$campagnodon_transaction['statut_migration_distant'], 'campagnodon'._LOG_ERREUR);
		campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
		return 0;
	}

	if (
		$campagnodon_transaction['type_transaction'] === 'don_mensuel_echeance'
		|| $campagnodon_transaction['type_transaction'] === 'adhesion_annuel_echeance'
	) {
		if ($campagnodon_transaction['statut_distant'] === null) {
			spip_log(
				__FUNCTION__.' La transaction campagnodon '.$id_campagnodon_transaction
				.' est une echeance ' . $campagnodon_transaction['type_transaction']
				.', et n\'a pas encore été synchronisée avec le système distant.'
				.' Il est temps d\'appeler le connecteur garantir_echeance_existe pour la créer si nécessaire.',
				'campagnodon'._LOG_DEBUG
			);
			// NB: si jamais on a des exécutions parallèles de cette fonction pour une même transaction,
			// on compte sur le système distant pour gérer
			// (et éventuellement échouer l'une des execution avec une erreur de type «duplicate key»).
			$fonction_garantir_echeance_existe = campagnodon_fonction_connecteur($mode, 'garantir_echeance_existe');
			if (!$fonction_garantir_echeance_existe) {
				spip_log(__FUNCTION__.' Campagnodon mal configuré, impossible de trouver le connecteur garantir_echeance_existe pour le mode: '.$mode, 'campagnodon'._LOG_ERREUR);
				campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
				return 0;
			}

			if (!$campagnodon_transaction['id_campagnodon_transaction_parent']) {
				spip_log(__FUNCTION.' La transaction campagnodon '.$id_campagnodon_transaction.' est une echeance et n\'a pas d\'ID de parent, ce n\'est pas normal.', 'campagnodon'._LOG_ERREUR);
				campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
				return 0;
			}

			$campagnodon_transaction_parent = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($campagnodon_transaction['id_campagnodon_transaction_parent']));
			if (!$campagnodon_transaction_parent) {
				spip_log(__FUNCTION__.' spip_campagnodon_transactions parent introuvable: '.$campagnodon_transaction['id_campagnodon_transaction_parent'], 'campagnodon'._LOG_ERREUR);
				campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
				return 0;
			}

			// NB: ici normalement seul les cas don_mensuel_echeance/adhesion_annuel_echeance sont utiles.
			$distant_operation_type = form_utils_operation_type_distant($campagnodon_transaction['type_transaction']);

			$url_transaction = generer_url_ecrire('campagnodon_transaction', 'id_campagnodon_transaction='.htmlspecialchars($id_campagnodon_transaction), false, false);

			$params_garantir = [
				// 'payment_url' => $url_paiement, TODO?
				'transaction_url' => $url_transaction,
				'operation_type' => $distant_operation_type,
			];

			if (substr($campagnodon_transaction['type_transaction'], 0, 9) === 'adhesion_') {
				spip_log(
					__FUNCTION__.': on est sur une échéance d\'adhésion, on créé les contributions qui vont bien.',
					'campagnodon'._LOG_DEBUG
				);
				// FIXME: pour les adhésions mensuelles, voir comment faire pour le magazine.

				$params_garantir['contributions'] = [];

				// On traite le cas du magazine, le cas échéant.
				$montant_total = $transaction['montant'];
				$adhesion_magazine_prix = form_init_get_adhesion_magazine_prix($mode_options, 'adhesion');
				if (
					$adhesion_magazine_prix > 0
					&& intval($montant_total) < $adhesion_magazine_prix + 1
				) {
					$adhesion_magazine_prix = intval($montant_total) - 1;
					if ($adhesion_magazine_prix <= 0) {
						spip_log(
							__FUNCTION__
							." J'arrive à un prix de magazine nul pour spip_campagnodon_transactions="
							.$id_campagnodon_transaction,
							'campagnodon'._LOG_ERREUR
						);
						campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
						return 0;
					}
				}

				if ($adhesion_magazine_prix > 0) {
					$params_garantir['contributions'][] = [
						'financial_type' => campagnodon_traduit_financial_type($mode_options, 'adhesion_magazine'),
						'amount' => strval($adhesion_magazine_prix),
						'currency' => 'EUR',
						'membership' => form_utils_traduit_adhesion_type($mode_options, 'magazine'),
						// Note: on n'a pas besoin de membership_option pour le magazine PDF:
						// L'abonnement côté CiviCRM va être prolongé, et garder son attribut.
						// 'source' => $source
					];
				} else {
					$adhesion_magazine_prix = 0; // juste par sécurité.
				}

				$params_garantir['contributions'][] = [
					'financial_type' => campagnodon_traduit_financial_type(
						$mode_options,
						'adhesion' // Note: pour les adhésions, un seul financial_type.
					),
					'currency' => 'EUR',
					'amount' => strval(intval($montant_total) - intval($adhesion_magazine_prix)),
					'membership' => form_utils_traduit_adhesion_type($mode_options, 'adhesion')
				];
			} else {
				spip_log(
					__FUNCTION__.': on est sur une échéance d\'adhésion, on créé les contributions qui vont bien.',
					'campagnodon'._LOG_DEBUG
				);
				$params_garantir['contributions'] = [
					[
						'financial_type' => campagnodon_traduit_financial_type(
							$mode_options,
							'don_mensuel_echeance'
						),
						'currency' => 'EUR',
						'amount' => $transaction['montant']
					]
				];
			}
			// Pour les paiements récurrents, on a spip_transactions.date_paiement qui peut être dans le futur !
			// En effet, dans le cas de paiements SEPA, on a une première requête pour tester environ 15 jours avant.
			// La date à retenir est celle du prélèvement effectif !
			if (!empty($transaction['date_paiement']) && substr($transaction['date_paiement'], 0, 4) != '0000') {
				$params_garantir['contribution_date'] = $transaction['date_paiement'];
			}

			try {
				$resultat = $fonction_garantir_echeance_existe($mode_options, $campagnodon_transaction_parent['transaction_distant'], $campagnodon_transaction['transaction_distant'], $params_garantir);
				if (false === $resultat) {
					spip_log(
						__FUNCTION__." Il semblerait que l\'appel au connecteur garantir_echeance_existe a échoué pour spip_campagnodon_transactions=".$id_campagnodon_transaction
						. ', il faut reprogrammer la synchronisation. '
						. 'NB: Si l\'erreur est de type «duplicate key», il se peut que ce soit dû à des executions parallèles, et Campagnodon devrait retomber sur ses pieds.',
						'campagnodon'._LOG_ERREUR
					);
					campagnodon_queue_synchronisation($id_campagnodon_transaction, $nb_tentatives + 1);
					return 0;
				}
			} catch (Exception $e) {
				spip_log(
					__FUNCTION__
					. ' Il semblerait que la garantir_echeance_existe ait échoué pour spip_campagnodon_transactions='.$id_campagnodon_transaction
					. '. NB: Si l\'erreur est de type «duplicate key», il se peut que ce soit dû à des executions parallèles, et Campagnodon devrait retomber sur ses pieds.'
					. " L\'erreur: ".$e->getMessage(),
					'campagnodon'._LOG_ERREUR
				);
				campagnodon_queue_synchronisation($id_campagnodon_transaction, $nb_tentatives + 1);
				return 0;
			}
		}
	}

	$statut = $transaction['statut'];
	$statut_distant = null;
	// Parfois spip bank ajoute des choses derrière les statuts. Par ex «echec[fail]». Dans le doute, on applique cette règle pour tous les statuts.
	if (strncmp($statut, 'ok', 2) == 0) {
		$statut_distant = 'ok';
	} elseif (strncmp($statut, 'echec', 5) == 0) {
		$statut_distant = 'echec';
	} elseif (strncmp($statut, 'commande', 8) == 0) {
		$statut_distant = 'attente'; // FIXME: ou alors il faut différencier commande et attente coté CiviCRM ?
	} elseif (strncmp($statut, 'attente', 7) == 0) {
		$statut_distant = 'attente';
	} elseif (strncmp($statut, 'abandon', 7) == 0) {
		$statut_distant = 'abandon';
	} elseif (strncmp($statut, 'rembourse', 8) == 0) {
		$statut_distant = 'rembourse';
	} else {
		spip_log(__FUNCTION__." Je ne sais pas synchroniser le statut '".$statut."' pour spip_campagnodon_transactions=".$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
		return 0;
	}

	$mode_paiement = $transaction['mode'];
	if (empty($mode_paiement)) {
		// on peut ne pas encore avoir de mode de paiement, auquel cas on ne le synchronise pas.
		$mode_paiement_distant = null;
	} elseif (preg_match('/^([^\/]*)/', $mode_paiement, $matches)) {
		$mode_paiement_distant = _traduit_type_paiement($mode_options, $matches[1], $transaction['refcb']);
	} else {
		spip_log(__FUNCTION__." Je ne sais pas synchroniser le mode '".$mode_paiement."' pour spip_campagnodon_transactions=".$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
		return 0;
	}

	$fonction_maj_statut = campagnodon_fonction_connecteur($mode, 'maj_statut');
	if (!$fonction_maj_statut) {
		spip_log(__FUNCTION__.' Campagnodon mal configuré, impossible de trouver le connecteur maj_statut pour le mode: '.$mode, 'campagnodon'._LOG_ERREUR);
		campagnodon_maj_sync_statut($id_campagnodon_transaction, 'echec');
		return 0;
	}

	$failed = false;
	$nouveau_statut_distant = null;
	$nouveau_statut_recurrence_distant = null;
	try {
		$resultat = $fonction_maj_statut(
			$mode_options,
			$campagnodon_transaction['transaction_distant'],
			$statut_distant,
			$mode_paiement_distant,
			$campagnodon_transaction['statut_recurrence']
		);
		if (false === $resultat) {
			spip_log(__FUNCTION__.' Il semblerait que la synchronisation a échoué pour spip_campagnodon_transactions='.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
			$failed = true;
		} else {
			spip_log(__FUNCTION__.' Appel id_campagnodon_transaction='.$id_campagnodon_transaction.', tentative #'.$nb_tentatives.', le connecteur a réussi à mettre à jour.', 'campagnodon'._LOG_DEBUG);
			$nouveau_statut_distant = is_array($resultat) && array_key_exists('statut', $resultat) ? $resultat['statut'] : null;
			$nouveau_statut_recurrence_distant = is_array($resultat) && array_key_exists('statut_recurrence', $resultat) ? $resultat['statut_recurrence'] : null;
		}
	} catch (Exception $e) {
		spip_log(__FUNCTION__.' Il semblerait que la synchronisation a échoué pour spip_campagnodon_transactions='.$id_campagnodon_transaction.': '.$e->getMessage(), 'campagnodon'._LOG_ERREUR);
		$failed = true;
	}

	if ($failed) {
		campagnodon_queue_synchronisation($id_campagnodon_transaction, $nb_tentatives + 1);
		return 0;
	}

	spip_log(__FUNCTION__. ' Appel id_campagnodon_transaction='.$id_campagnodon_transaction.', tentative #'.$nb_tentatives.', tout est ok.', 'campagnodon'._LOG_DEBUG);
	campagnodon_maj_sync_statut($id_campagnodon_transaction, 'ok', $nouveau_statut_distant ?? '???', $nouveau_statut_recurrence_distant);
	return 1;
}

function campagnodon_montants_par_defaut($type) {
	if (
		defined('_CAMPAGNODON_MONTANTS')
		&& is_array(_CAMPAGNODON_MONTANTS)
		&& array_key_exists($type, _CAMPAGNODON_MONTANTS)
		&& is_array(_CAMPAGNODON_MONTANTS[$type])
	) {
		return _CAMPAGNODON_MONTANTS[$type];
	}

	if ($type === 'don') {
		return ['30','50','100','200'];
	}
	if ($type === 'don_recurrent') {
		return ['6','15','30','50'];
	}
	if ($type === 'adhesion' || $type === 'adhesion_recurrent') {
		return [
			'13[-450]',
			'21[450-900]',
			'35[900-1200]',
			'48[1200-1600]',
			'65[1600-2300]',
			'84[2300-3000]',
			'120[3000-4000]',
			'160[4000-]'
		];
	}

	return ['10', '20', '40'];
}

function campagnodon_calcul_libelle_source($mode_options, $campagne) {
	if (
		!is_array($mode_options)
		|| !array_key_exists('libelle_source', $mode_options)
		|| empty($campagne)
	) {
		return null;
	}
	$s = $mode_options['libelle_source'];
	$s = preg_replace('/\{ID_CAMPAGNE\}/', $campagne['id_campagnodon_campagne'], $s);
	$s = preg_replace('/\{TITRE_CAMPAGNE\}/', $campagne['titre'], $s);
	return $s;
}

function campagnodon_don_recurrent_active() {
	return defined('_CAMPAGNODON_DON_RECURRENT') && _CAMPAGNODON_DON_RECURRENT === true;
}

/**
 * Retourne une liste de types de contributions vers lesquels on peut convertir la transaction.
 * @param $id_campagnodon_transaction
 * @return array
 */
function campagnodon_peut_convertir_transaction_en($id_campagnodon_transaction) {
	$campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
	return array_keys(_campagnodon_peut_convertir_transaction_en($campagnodon_transaction));
}

function _campagnodon_peut_convertir_transaction_en($campagnodon_transaction) {
	if (!$campagnodon_transaction) {
		return array();
	}
	include_spip('inc/campagnodon.utils');
	$mode_options = campagnodon_mode_options($campagnodon_transaction['mode']);
	if (!array_key_exists('conversion', $mode_options) || !is_array($mode_options['conversion'])) {
		return array();
	}

	$conversions = $mode_options['conversion'];
	if (
		!array_key_exists($campagnodon_transaction['type_transaction'], $conversions)
		|| !is_array($conversions[$campagnodon_transaction['type_transaction']])
	) {
		return array();
	}
	$conversions = $conversions[$campagnodon_transaction['type_transaction']];

	$result = [];
	foreach ($conversions as $nouveau_type => $options) {
		if (!array_key_exists('statuts_distants', $options)) {
			continue;
		}
		if (!is_array($options['statuts_distants'])) {
			continue;
		}
		if (!in_array($campagnodon_transaction['statut_distant'], $options['statuts_distants'])) {
			continue;
		}
		$result[$nouveau_type] = $options;
	}

	return $result;
}

/**
 * Effectue la conversion d'une transaction.
 * @param $id_campagnodon_transaction L'id de la transaction campagnodon
 * @param $nouveau_type Le type en lequel convertir
 */
function campagnodon_converti_transaction_en($id_campagnodon_transaction, $nouveau_type) {
	$campagnodon_transaction = sql_fetsel('*', 'spip_campagnodon_transactions', 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
	if (!$campagnodon_transaction) {
		spip_log('Transaction introuvable: "'.$id_campagnodon_transaction.'"', 'campagnodon'._LOG_ERREUR);
		return false;
	}

	$peut_convertir_en = _campagnodon_peut_convertir_transaction_en($campagnodon_transaction);
	if (!array_key_exists($nouveau_type, $peut_convertir_en) || empty($peut_convertir_en[$nouveau_type])) {
		spip_log('On ne peut pas convertir la transaction: "'.$id_campagnodon_transaction.'" en: "'.$nouveau_type.'"', 'campagnodon'._LOG_ERREUR);
		return false;
	}
	$peut_convertir_en_options = $peut_convertir_en[$nouveau_type];

	$mode = $campagnodon_transaction['mode'];
	$mode_options = campagnodon_mode_options($mode);

	$fonction_convertir = campagnodon_fonction_connecteur($mode, 'convertir');
	if (!$fonction_convertir) {
		spip_log('Il n\'y a pas de connecteur "convertir" pour la transaction: "'.$id_campagnodon_transaction.'"', 'campagnodon'._LOG_ERREUR);
		return false;
	}

	if ($nouveau_type === 'don') {
		$nouveau_type_distant = 'donation';
	} elseif ($nouveau_type === 'adhesion') {
		$nouveau_type_distant = 'membership';
	} else {
		$nouveau_type_distant = $nouveau_type;
	}

	$parametres_api = array_key_exists('parametres_api', $peut_convertir_en_options) ? $peut_convertir_en_options['parametres_api'] ?? [] : [];

	// Appel de l'API de conversion.
	$resultat = $fonction_convertir($mode_options, $campagnodon_transaction['transaction_distant'], $nouveau_type_distant, $parametres_api);
	$resultat_nouveau_type_distant = is_array($resultat) && array_key_exists('operation_type', $resultat) ? $resultat['operation_type'] : null;

	if (empty($resultat_nouveau_type_distant) || $resultat_nouveau_type_distant !== $nouveau_type_distant) {
		spip_log('L\'API de conversion semble avoir échoué pour la transaction: "'.$id_campagnodon_transaction.'"', 'campagnodon'._LOG_ERREUR);
		return false;
	}

	// On met à jour le type sur campagnodon_transactions
	if (false === sql_updateq(
		'spip_campagnodon_transactions',
		[
			'type_transaction' => $nouveau_type,
			'statut_distant' => null, // on reset le statut_distant, il va être resynchronisé juste après.
		],
		'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
	)) {
		spip_log('Erreur à la modification de la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon'._LOG_ERREUR);
		return false;
	}

	spip_log('On vient de convertir la transaction, on doit planifier une synchronisation pour la transaction Campagnodon: "'.$id_campagnodon_transaction.'"', 'campagnodon'._LOG_DEBUG);
	campagnodon_queue_synchronisation($id_campagnodon_transaction);
	return true;
}
