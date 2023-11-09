<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

class CampagnodonException extends Exception { // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
	protected $error_label = '';
	public function __construct($message = '', $error_label = '', $code = 0, $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->error_label = $error_label;
	}

	public function getErrorLabel() {
		return _T($this->error_label);
	}
}

/**
* Declarer les champs, y integrer les valeurs par defaut, et initialiser les variables pour le formulaire.
*/
function formulaires_campagnodon_charger_dist(
	$form_type,
	$id_campagne = null,
	$arg_liste_montants = null,
	$arg_souscriptions_perso = null,
	$arg_don_recurrent = null,
	$arg_liste_montants_recurrent = null,
	$arg_liste_montants_adhesion = null,
	$arg_liste_montants_adhesion_recurrent = null
) {
	if ($form_type !== 'don' && $form_type !== 'adhesion' && $form_type !== 'don+adhesion') {
		spip_log('Type de formulaire Campagnodon inconnu: '.$form_type, 'campagnodon'._LOG_ERREUR);
		return false;
	}

	include_spip('inc/campagnodon/form/init');
	include_spip('inc/campagnodon/form/utils');

	list($choix_type_desc, $choix_type_defaut) = form_init_get_choix_type($form_type);
	$choix_recurrence_desc = form_init_choix_recurrence($form_type, $arg_don_recurrent);

	$campagne = form_utils_get_campagne_ouverte($id_campagne);
	if (empty($campagne)) {
		spip_log('Campagne introuvable: '.$id_campagne, 'campagnodon'._LOG_ERREUR);
		return false;
	}

	include_spip('inc/campagnodon.utils');
	$mode_options = campagnodon_mode_options($campagne['origine']);

	$config_montants = form_init_liste_montants_campagne(
		$form_type,
		$id_campagne,
		$arg_liste_montants,
		$arg_don_recurrent,
		$arg_liste_montants_recurrent,
		$arg_liste_montants_adhesion,
		$arg_liste_montants_adhesion_recurrent
	);
	if (!$config_montants) {
		spip_log('Liste de montants invalide', 'campagnodon'._LOG_ERREUR);
		return false;
	}
	$civilites = form_init_liste_civilites($mode_options);
	$souscriptions_optionnelles = form_init_liste_souscriptions_optionnelles($form_type, $mode_options, $arg_souscriptions_perso);

	$values = [
		'_form_type' => $form_type,
		'_souscriptions_optionnelles' => $souscriptions_optionnelles,
		'_avec_don' => $form_type === 'don' || $form_type === 'don+adhesion',
		'_avec_adhesion' => $form_type === 'adhesion' || $form_type === 'don+adhesion',
		'_choix_type_desc' => $choix_type_desc,
		'_choix_recurrence_desc' => $choix_recurrence_desc,
		'_propositions_montants' => $config_montants['propositions'],
		'_civilites' => $civilites,
		'choix_type' => $choix_type_defaut,
		'montant' => '',
		'montant_libre' => '',
		'email' => '',
		'civilite' => '',
		'prenom' => '',
		'nom' => '',
		'date_naissance' => '',
		'adresse' => '',
		'complement_adresse_1' => '',
		'complement_adresse_2' => '',
		'code_postal' => '',
		'ville' => '',
		'pays' => defined('_CAMPAGNODON_PAYS_DEFAULT') ? _CAMPAGNODON_PAYS_DEFAULT : '',
		'telephone' => '',
	];

	if (!empty($values['_choix_recurrence_desc'])) {
		$values['choix_recurrence'] = '';
	}

	if ($form_type === 'adhesion' || $form_type === 'don+adhesion') {
		$adhesion_magazine_prix = form_init_get_adhesion_magazine_prix($mode_options, $form_type);
		if ($adhesion_magazine_prix > 0) {
			$values['_adhesion_magazine'] = true;
			$values['_adhesion_magazine_prix'] = $adhesion_magazine_prix;
		} else {
			$values['_adhesion_magazine'] = false;
		}
	}

	if ($form_type === 'don' || $form_type === 'don+adhesion') {
		$values['recu_fiscal'] = '';
	}

	foreach ($souscriptions_optionnelles as $cle => $souscription_optionnelle) {
		$values['souscription_optionnelle_'.$cle] = '';
	}

	return $values;
}

function formulaires_campagnodon_verifier_dist(
	$form_type,
	$id_campagne = null,
	$arg_liste_montants = null,
	$arg_souscriptions_perso = null,
	$arg_don_recurrent = null,
	$arg_liste_montants_recurrent = null,
	$arg_liste_montants_adhesion = null,
	$arg_liste_montants_adhesion_recurrent = null
) {
	$erreurs = [];
	$verifier = charger_fonction('verifier', 'inc/');
	include_spip('inc/campagnodon/form/init');
	include_spip('inc/campagnodon/form/utils');

	$obligatoires = [];

	list($choix_type_desc, $choix_type_defaut) = form_init_get_choix_type($form_type);
	$choix_recurrence_desc = form_init_choix_recurrence($form_type, $arg_don_recurrent);

	$campagne = form_utils_get_campagne_ouverte($id_campagne);
	if (empty($campagne)) {
		$erreurs['message_erreur'] = _T('campagnodon:campagne_invalide');
	}

	$choix_type = _request('choix_type');

	include_spip('inc/campagnodon.utils');
	$mode_options = campagnodon_mode_options($campagne['origine']);

	$config_montants = form_init_liste_montants_campagne(
		$form_type,
		$id_campagne,
		$arg_liste_montants,
		$arg_don_recurrent,
		$arg_liste_montants_recurrent,
		$arg_liste_montants_adhesion,
		$arg_liste_montants_adhesion_recurrent
	);
	$civilites = form_init_liste_civilites($mode_options);

	// On veut les coordonnées complètes dans ces cas:
	$coordonnees_completes = $choix_type === 'adhesion' || _request('recu_fiscal') == '1';

	$adhesion_magazine_prix = form_init_get_adhesion_magazine_prix($mode_options, $form_type);

	$obligatoires = ['choix_type', 'email']; // Pas besoin de 'montant', il sera testé plus loin

	if (!empty($choix_recurrence_desc)) {
		$obligatoires[] = 'choix_recurrence';
	}

	if ($coordonnees_completes) {
		array_push($obligatoires, 'prenom', 'nom', 'adresse', 'code_postal', 'ville', 'pays');
	}

	foreach ($obligatoires as $obligatoire) {
		if (!_request($obligatoire)) {
			$erreurs[$obligatoire] = _T('info_obligatoire');
		}
	}

	$choix_type = _request('choix_type');
	if (!array_key_exists($choix_type, $choix_type_desc)) {
		$erreurs['choix_type'] = _T('campagnodon_form:erreur_valeur_invalide');
	}

	$choix_recurrence = null;
	if (!empty($choix_recurrence_desc)) {
		$choix_recurrence = _request('choix_recurrence');
		if (
			!array_key_exists($choix_type, $choix_recurrence_desc)
			|| ! array_key_exists($choix_recurrence, $choix_recurrence_desc[$choix_type])
		) {
			$erreurs['choix_recurrence'] = _T('campagnodon_form:erreur_valeur_invalide');
		}
	}

	if ($e = _request('email') and !email_valide($e)) {
		$erreurs['email'] = _T('campagnodon_form:erreur_email_invalide');
	}

	$montant = form_utils_read_montant($config_montants, $choix_type, $choix_recurrence);
	if ($montant === null) {
		$erreurs['montant'] = _T('info_obligatoire');
	} else {
		// On vérifie qu'on est dans les bornes autorisées.
		$don_min = defined('_CAMPAGNODON_DON_MINIMUM') && is_numeric(_CAMPAGNODON_DON_MINIMUM)
			? _CAMPAGNODON_DON_MINIMUM
			: 1;
		$don_max = defined('_CAMPAGNODON_DON_MAXIMUM') && is_numeric(_CAMPAGNODON_DON_MAXIMUM)
			? _CAMPAGNODON_DON_MAXIMUM
			: 10000000;

		// Si c'est une adhésion, il faut en plus être au moins 1 euro au dessus du prix du magazine.
		// FIXME: pour l'adhésion minimum (hors maganize),
		//			je pars du principe que c'est la meme valeur que pour les dons.
		//			Il faudrait peut être une borne spécifique aux adhésions.
		if ($choix_type === 'adhesion' && $adhesion_magazine_prix + 1 > $don_min) {
			$don_min = $adhesion_magazine_prix + 1;
		}

		if (
			$erreur = $verifier($montant, 'entier', array('min' => $don_min))
			or // on teste en 2 fois, pour que le message d'erreur n'affiche qu'une seule borne.
			$erreur = $verifier($montant, 'entier', array('max' => $don_max))
		) {
			$erreurs['montant'] = $erreur;
		}
	}

	if ($coordonnees_completes) {
		$pays = _request('pays');
		$ret = sql_select('nom', 'spip_pays', 'code='.sql_quote($pays));
		if (sql_count($ret) != 1) {
			$erreurs['pays'] = _T('campagnodon_form:erreur_pays_invalide');
		}

		$civilite = _request('civilite');
		if (!empty($civilite) && !array_key_exists($civilite, $civilites)) {
			$erreurs['civilite'] = _T('campagnodon_form:erreur_civilite_invalide');
		}

		$date_naissance = _request('date_naissance');
		if (!empty($date_naissance)) {
			if ($erreur = $verifier($date_naissance, 'date', array('format' => 'amj'))) {
				$erreurs['date_naissance'] = $erreur;
			}
		}

		$code_postal = _request('code_postal');
		if (!empty($code_postal)) {
			if ($erreur = $verifier($code_postal, 'code_postal', array('pays' => $pays))) {
				$erreurs['code_postal'] = $erreur;
			}
		}

		$telephone = _request('telephone');
		if (!empty($telephone)) {
			if ($erreur = $verifier($telephone, 'telephone')) {
				$erreurs['telephone'] = $erreur;
			}
		}
	}

$erreurs['message_erreur'] = 'ceci est un test';
	return $erreurs;
}

function formulaires_campagnodon_traiter_dist(
	$form_type,
	$id_campagne = null,
	$arg_liste_montants = null,
	$arg_souscriptions_perso = null,
	$arg_don_recurrent = null,
	$arg_liste_montants_recurrent = null,
	$arg_liste_montants_adhesion = null,
	$arg_liste_montants_adhesion_recurrent = null
) {
	try {
		spip_log('traiter_dist' . $form_type . ':'. $id_campagne);
		$type = $form_type; // TODO: lire la valeur du champ

		include_spip('inc/campagnodon/form/init');
		include_spip('inc/campagnodon/form/utils');

		$campagne = form_utils_get_campagne_ouverte($id_campagne);
		if (empty($campagne)) {
			throw new CampagnodonException('Campagne invalide au moment de l\'enregistrement', 'campagnodon:campagne_invalide');
		}

		$config_montants = form_init_liste_montants_campagne(
			$form_type,
			$id_campagne,
			$arg_liste_montants,
			$arg_don_recurrent,
			$arg_liste_montants_recurrent,
			$arg_liste_montants_adhesion,
			$arg_liste_montants_adhesion_recurrent
		);
		$recu_fiscal = $form_type === 'adhesion' || _request('recu_fiscal') == '1'; // on veut toujours un reçu pour les adhésions
		$adhesion_avec_don = $form_type === 'adhesion' && _request('adhesion_avec_don') == '1';
		list ($montant, $montant_est_recurrent) = ($form_type !== 'adhesion' || $adhesion_avec_don) ? form_init_get_form_montant($config_montants) : null;
		$montant_adhesion = ($form_type === 'adhesion') ? form_init_get_form_montant_adhesion($config_montants) : null;

		die('FIXME');
		$type_transaction = ($form_type === 'don' && $montant_est_recurrent) ? 'don_mensuel' : $form_type;

		$montant_total = 0;
		if ($montant) {
			$montant_total+= $montant;
		}
		if ($montant_adhesion) {
			$montant_total+= $montant_adhesion;
		}

		include_spip('inc/campagnodon.utils');
		$mode_options = campagnodon_mode_options($campagne['origine']);
		if (!$mode_options['type']) {
			throw new CampagnodonException("Campagnodon non configuré, mode inconnu: '".$campagne['origine']."'.", 'campagnodon:erreur_sauvegarde');
		}
		$fonction_nouvelle_contribution = campagnodon_fonction_connecteur($campagne['origine'], 'nouvelle_contribution');
		if (!$fonction_nouvelle_contribution) {
			throw new CampagnodonException("Campagnodon mal configuré, impossible de trouver le connecteur nouvelle_contribution pour le mode: '".$campagne['origine']."'.", 'campagnodon:erreur_sauvegarde');
		}
		$source = campagnodon_calcul_libelle_source($mode_options, $campagne);

		$adhesion_magazine_prix = form_init_get_adhesion_magazine_prix($mode_options, $form_type); // FIXME: $form_type ou $type ?

		$id_campagnodon_transaction = sql_insertq('spip_campagnodon_transactions', [
			'id_campagnodon_campagne' => $id_campagne,
			'type_transaction' => $type_transaction,
			'mode' => $campagne['origine'],
			'statut_recurrence' => $montant_est_recurrent ? 'initialisation' : null
		]);
		if (!($id_campagnodon_transaction > 0)) {
			throw new CampagnodonException('Erreur à la création de la transaction campagnodon.', 'campagnodon:erreur_sauvegarde');
		}

		$inserer_transaction = charger_fonction('inserer_transaction', 'bank');
		$transaction_options = [
			// 'auteur' => _request('email'), // FIXME: peut-on se passer de cette info ?
			'parrain' => 'campagnodon',
			'tracking_id' => $id_campagnodon_transaction,
			'force' => true
		];
		if (!(
			$id_transaction = $inserer_transaction($montant_total, $transaction_options)
			and $hash = sql_getfetsel('transaction_hash', 'spip_transactions', 'id_transaction=' . intval($id_transaction))
		)) {
			throw new CampagnodonException('Erreur à la création de la transaction '.$id_campagnodon_transaction, 'campagnodon:erreur_sauvegarde');
		}

		$url_paiement = generer_url_public('campagnodon-payer', array('id_transaction'=>$id_transaction, 'transaction_hash'=>$hash), false, false);
		$url_paiement_redirect = $url_paiement;
		if (defined('_CAMPAGNODON_GERER_WIDGETS') && _CAMPAGNODON_GERER_WIDGETS === true) {
			// On est ici dans un code hautement expérimental. Voir la doc de _CAMPAGNODON_GERER_WIDGETS.
			$widget_mode = _request('mode', $_GET);
			if ($widget_mode === 'frame') {
				$url_paiement_redirect = generer_url_public('widget', array(
					'id_transaction'=>$id_transaction,
					'transaction_hash'=>$hash,
					'mode' => $widget_mode,
					'type' => 'campagnodon-payer'
				), false, false);
			}
		}
		$url_transaction = generer_url_ecrire('campagnodon_transaction', 'id_campagnodon_transaction='.htmlspecialchars($id_campagnodon_transaction), false, false);

		include_spip('inc/campagnodon.utils');
		$transaction_idx_distant = get_transaction_idx_distant($mode_options, $id_campagnodon_transaction);
		if (false === sql_updateq(
			'spip_campagnodon_transactions',
			[
				'id_transaction' => $id_transaction,
				'transaction_distant' => $transaction_idx_distant
			],
			'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
		)) {
			throw new CampagnodonException('Erreur à la modification de la transaction campagnodon '.$id_campagnodon_transaction, 'campagnodon:erreur_sauvegarde');
		}

		$souscriptions_optionnelles = form_init_liste_souscriptions_optionnelles($form_type, $mode_options, $arg_souscriptions_perso); // FIXME: $form_type ou $type ?

		$contributions = null;
		if ($type === 'don') {
			$contributions = [
				[
					'financial_type' => campagnodon_traduit_financial_type($mode_options, $montant_est_recurrent ? 'don_mensuel' : 'don'),
					'amount' => $montant,
					'currency' => 'EUR',
					'source' => $source
				]
			];
		} elseif ($type === 'adhesion') {
			$contributions = [];
			if ($adhesion_magazine_prix > 0) {
				$contribution_magazine = [
					'financial_type' => campagnodon_traduit_financial_type($mode_options, 'adhesion_magazine'),
					'amount' => strval($adhesion_magazine_prix),
					'currency' => 'EUR',
					'membership' => form_utils_traduit_adhesion_type($mode_options, 'magazine'),
					'source' => $source
				];

				// On cherche l'éventuel souscription optionnelle special:magazine_pdf.
				foreach ($souscriptions_optionnelles as $cle => $so) {
					if ($so['type'] !== 'special:magazine_pdf') {
						continue;
					}
					$pdf_seulement_champ = $so['cle_distante'];
					// FIXME: la façon dont est passé ce paramètre n'est vraiment pas propre.
					$contribution_magazine['membership_option'] = $pdf_seulement_champ.':'.(_request('souscription_optionnelle_'.$cle) == '1' ? '1' : '0');
				}

				$contributions[] = $contribution_magazine;
			}

			$contributions[] = [
				'financial_type' => campagnodon_traduit_financial_type($mode_options, 'adhesion'),
				'amount' => strval(intval($montant_adhesion) - intval($adhesion_magazine_prix)),
				'currency' => 'EUR',
				'membership' => form_utils_traduit_adhesion_type($mode_options, 'adhesion'),
				'source' => $source
			];

			if ($adhesion_avec_don) {
				$contributions[] = [
					'financial_type' => campagnodon_traduit_financial_type($mode_options, 'don'),
					'amount' => $montant,
					'currency' => 'EUR',
					'source' => $source
				];
			}
		} else {
			throw new CampagnodonException("Type inconnu: '".$type."'", 'campagnodon:erreur_sauvegarde');
		}

		$distant_operation_type = $type_transaction;
		switch ($type_transaction) {
			case 'don':
				$distant_operation_type = 'donation';
				break;
			case 'adhesion':
				$distant_operation_type = 'membership';
				break;
			case 'don_mensuel':
				$distant_operation_type = 'monthly_donation';
				break;
			// NB: et on a ce cas, qui est initié ailleurs dans le code
			// case 'don_mensuel_echeance': $distant_operation_type = 'monthly_donation_due'; break;
			// case 'don_mensuel_migre'...
		}

		$params = array(
			'campagnodon_version' => '1', // numéro de version pour le format de donnée (pour s'assurer de la compatibilité des API)
			'email' => trim(_request('email')),
			'source' => $source,
			'operation_type' => $distant_operation_type,
			'contributions' => $contributions,
			'campaign_id' => $campagne['id_origine'],
			'transaction_idx' => $transaction_idx_distant,
			'payment_url' => $url_paiement,
			'transaction_url' => $url_transaction,
			'optional_subscriptions' => array()
			// 'payment_method' => 'transfer' // FIXME: use the correct value
		);
		if ($montant_est_recurrent) {
			$params['is_recurring'] = true;
		}
		if ($recu_fiscal || $adhesion_avec_don) {
			// FIXME: je n'ai pas réussi à faire marcher la phase de normalisation ci-dessous.
			// $date_naissance = _request('date_naissance');
			// $date_naissance_normalisee = null;
			// if (!empty($date_naissance)) {
			//   $verifier = charger_fonction('verifier', 'inc/');
			//   $verifier($date_naissance, 'date', array('format' => 'amj', 'normaliser' => 'date'), $date_naissance_normalisee);
			// }

			$params = array_merge($params, array(
				'tax_receipt' => true,
				'prefix' => _request('civilite'),
				'first_name' => trim(_request('prenom')),
				'last_name' => trim(_request('nom')),
				'birth_date' => _request('date_naissance'),
				'street_address' => trim(_request('adresse')),
				'supplemental_address_1' => trim(_request('complement_adresse_1')),
				'supplemental_address_2' => trim(_request('complement_adresse_2')),
				'postal_code' => trim(_request('code_postal')),
				'city' => trim(_request('ville')),
				'country' => _request('pays'), // FIXME: vérifier que les valeurs sont bien compatibles avec celles de l'api CiviCRM.
				'phone' => trim(_request('telephone'))
			));

			// spip_log('Params contact CiviCRM: ' . json_encode($params), 'campagnodon'._LOG_DEBUG);
		}

		foreach ($souscriptions_optionnelles as $cle => $souscription_optionnelle) {
			if (_request('souscription_optionnelle_'.$cle) == '1') {
				if (!$souscription_optionnelle['type']) {
					throw new CampagnodonException("Campagnodon mal configuré, souscription_optionnelle mal configurée: '".$cle."'.", 'campagnodon:erreur_sauvegarde');
				}
				if (substr($souscription_optionnelle['type'], 0, 8) === 'special:') {
					// Cas particulier, on passe.
					continue;
				}
				$params['optional_subscriptions'][] = array(
					'name' => $cle,
					'type' => $souscription_optionnelle['type'],
					'key' => $souscription_optionnelle['cle_distante'] ?? null,
					'when' => $souscription_optionnelle['when'] ?? 'init'
				);
			}
		}

		try {
			$resultat = $fonction_nouvelle_contribution($mode_options, $params);
			if (is_array($resultat) && array_key_exists('status', $resultat)) {
				sql_update('spip_campagnodon_transactions', array(
					'statut_distant' => sql_quote($resultat['status']),
					'statut_recurrence_distant' => sql_quote($resultat['statut_recurrence'])
				), 'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction));
			}
		} catch (Exception $e) {
			throw new CampagnodonException('Erreur nouvelle_contribution mode='.$campagne['origine'].': ' . $e->getMessage(), 'campagnodon:erreur_sauvegarde');
		}

		// $update_campagnodon_transaction = [];
		// if (count($update_campagnodon_transaction) === 0 || false === sql_updateq(
		//   'spip_campagnodon_transactions',
		//   $update_campagnodon_transaction,
		//   'id_campagnodon_transaction='.sql_quote($id_campagnodon_transaction)
		// )) {
		//   throw new CampagnodonException("Erreur à la modification de la transaction campagnodon ".$id_campagnodon_transaction." (insertion infos CiviCRM)", "campagnodon:erreur_sauvegarde");
		// }

		include_spip('inc/campagnodon.utils');
		campagnodon_queue_synchronisation($id_campagnodon_transaction);

		return [
			'redirect' => $url_paiement_redirect,
			'editable' => false,
		];
	} catch (CampagnodonException $e) {
		spip_log($e->getMessage(), 'campagnodon'._LOG_ERREUR);
		return ['message_erreur' => $e->getErrorLabel()];
	}
}
