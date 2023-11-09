<?php
/**
 * Dans ce fichier, quelques fonctions qui permettent de construire le formulaire.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Retourne les variables décrivant les types à mettre dans le formulaire (don et/ou adhésion).
 * @param string $type le type de formulaire (comme passé à la balise campagnodon)
 * @return array les types, et la valeur par défaut le cas échéant.
 */
function form_init_get_choix_type($type) {
	$types = [];
	if ($type === 'don' || $type === 'don+adhesion') {
		$types['don'] = [
			'valeur' => 'don',
			'label' => _T('campagnodon_form:choix_don'),
		];
	}
	if ($type === 'adhesion' || $type === 'don+adhesion') {
		$types['adhesion'] = [
			'valeur' => 'adhesion',
			'label' => _T('campagnodon_form:choix_adhesion'),
		];
	}

	$choix_type_defaut = '';
	if ($type === 'don') {
		$choix_type_defaut = 'don';
	} elseif ($type === 'adhesion') {
		$choix_type_defaut = 'adhesion';
	}

	return [
		$types,
		$choix_type_defaut
	];
}

/**
 * Retourne les infos pour afficher le choix de récurrence (le cas échéant).
 * @param string $form_type Le type de formulaire (don, adhesion, don+adhesion).
 * @param string|null $arg_don_recurrent Le paramètre don_recurrent des formulaires.
 */
function form_init_choix_recurrence($form_type, $arg_don_recurrent) {
	include_spip('inc/campagnodon.utils');

	$choix_recurrence_desc = [];

	if ($form_type === 'don' || $form_type === 'don+adhesion') {
		$choix_recurrence_desc['don'] = [
			'unique' => [
				'valeur' => 'unique',
				'label' => _T('campagnodon_form:je_donne_une_fois')
			]
		];

		if ($arg_don_recurrent === '1' && campagnodon_don_recurrent_active()) {
			$choix_recurrence_desc['don']['mensuel'] = [
				'valeur' => 'mensuel',
				'label' => _T('campagnodon_form:je_donne_recurrent')
			];
		}
	}

	if ($form_type === 'adhesion' || $form_type === 'don+adhesion') {
		// TODO: ces valeurs doivent vérifier la conf, et éventuellement dépendre d'un argument adhesion_recurrent
		// TODO: de plus, il faudrait un équivalent à campagnodon_don_recurrent_active()
		$choix_recurrence_desc['adhesion'] = [
			'unique' => [
				'valeur' => 'unique',
				'label' => _T('campagnodon_form:jadhere_pour_un_an')
			]
		];

		if ($arg_don_recurrent === '1' && campagnodon_don_recurrent_active()) {
			$choix_recurrence_desc['adhesion']['annuel'] = [
				'valeur' => 'annuel',
				'label' => _T('campagnodon_form:jadhere_avec_renouvellement_automatique')
			];
		}
	}
	return $choix_recurrence_desc;
}

/**
 * Parse les montants configuré.
 * On part d'une liste qui peut avoir l'une des forme suivante:
 * - 12,25,40
 * - 10[-1000],20[1000-2000]
 * Le premier format génère une liste avec des libellés du type «12€».
 * Le deuxième, «12€ (entre X et Y de revenus mensuels)».
 * Cette fonction parse une valeur, et ajoute l'option à la liste de la valeur,
 * le libellé correspondant et éventuellement un texte de description.
 * @param $options Le tableau cle=>valeur des options
 * @param $montant Un (ou plusieurs) fragment(s) de la config («12» ou «20[1000-2000]).
 */
function form_init_parse_config_montant(&$options, $montant) {
	if (is_array($montant)) {
		foreach ($montant as $m) {
			if (!form_init_parse_config_montant($options, $m)) {
				return false;
			}
		}
		return true;
	}

	$montant = trim($montant);
	if (!preg_match('/^(?<montant>\d+)(?:\[(?<min>\d+)?-(?<max>\d+)?\])?$/', $montant, $matches)) {
		spip_log("Montant invalide dans la configuration du formulaire: '".$montant."'.", 'campagnodon'._LOG_ERREUR);
		return false;
	}
	$v = $matches['montant'];
	$label = $v . ' €';
	$desc = '';
	if (empty($matches['min']) && empty($matches['max'])) {
		// rien.
	} elseif (empty($matches['min'])) {
		$desc = _T('campagnodon_form:option_revenu_en_dessous', array(
			'montant' => $v,
			'max' => $matches['max']
		));
	} elseif (empty($matches['max'])) {
		$desc = _T('campagnodon_form:option_revenu_au_dessus', array(
			'montant' => $v,
			'min' => $matches['min']
		));
	} else {
		$desc = _T('campagnodon_form:option_revenu_entre', array(
			'montant' => $v,
			'min' => $matches['min'],
			'max' => $matches['max']
		));
	}
	$options[$v] = [
		'label' => $label,
		'desc' => $desc
	];
	return true;
}

/**
 * Initialise la liste des montants à afficher.
 * Également utilisé pour vérifier la validité de la saisie.
 */
function form_init_liste_montants_campagne(
	$form_type,
	$id_campagne,
	$arg_liste_montants,
	$arg_don_recurrent,
	$arg_liste_montants_recurrent,
	$arg_liste_montants_adhesion,
	$arg_liste_montants_adhesion_recurrent
) {
	include_spip('inc/campagnodon.utils');

	$r = [
		'propositions' => [],
		'don_recurrent' => false,
		'adhesion_recurrent' => false,
	];

	$_ajoute_propositions = function ($choix_type, $choix_recurrence, $combinaison, $arg_liste) use (&$r) {
		$liste_montants = [];
		$avec_libre = false;

		// On commence par récupérer une liste des montants.
		if (empty($arg_liste)) {
			// on n'a rien personnalisé dans le formulaire courant, on prend la config par défaut
			if (!form_init_parse_config_montant($liste_montants, campagnodon_montants_par_defaut($combinaison))) {
				throw new Error('Montant invalide dans la configuration du formulaire ('.$combinaison.').');
			}
			// Pour les dons, on ajoute toujours le montant libre.
			if ($choix_type === 'don') {
				$avec_libre = true;
			}
		} else {
			// on a donné des montants dans la configuration du formulaire, on les prend en compte.
			$liste = explode(',', $arg_liste);
			foreach ($liste as $montant) {
				if ($montant === 'libre') {
					$avec_libre = true;
					continue;
				}
				if (!form_init_parse_config_montant($liste_montants, $montant)) {
					throw new Error('Montant invalide dans la configuration du formulaire ('.$combinaison."): '".$montant."'.");
				}
			}
		}

		// maintenant on formate tout cela dans $r['propositions']
		foreach ($liste_montants as $valeur => $montant_desc) {
			$r['propositions'][] = [
				'valeur' => $valeur,
				'label' => $montant_desc['label'],
				'desc' => $montant_desc['desc'],
				'pour_combinaison' => $combinaison,
				'pour_type' => $choix_type,
				'pour_recurrence' => $choix_recurrence,
				'grand' => !empty($montant_desc['desc']), // si la case doit être "grande"
				'id' => 'montant_' . $choix_type . '_' . $choix_recurrence . '_' . $valeur, // ID html doit être unique
			];
		}
		if ($avec_libre) {
			// Note: si jamais plusieurs "pour_combinaison" acceptent les montants libres,
			// on va mettre autant de propositions.
			// Cela a 2 avantages:
			// - simplifier le code
			// - si un jour on veut mettre des attribut min/max sur les input,
			//		on pourra avoir des bornes différentes pour chaque cas.
			$r['propositions'][] = [
				'valeur' => 'libre',
				'label' => _T('campagnodon_form:montant_libre'),
				'desc' => _T('campagnodon_form:montant_libre_desc'),
				'pour_combinaison' => $combinaison,
				'pour_type' => $choix_type,
				'pour_recurrence' => $choix_recurrence,
				'grand' => true,
				'id' => 'montant_' . $choix_type . '_' . $choix_recurrence . '_libre', // l'ID html, doit être unique...
			];
		}
	};

	try {
		if ($form_type === 'don' || $form_type === 'don+adhesion') {
			$_ajoute_propositions('don', 'unique', 'don', $arg_liste_montants);
			if ($arg_don_recurrent === '1' && campagnodon_don_recurrent_active()) {
				$_ajoute_propositions('don', 'mensuel', 'don_recurrent', $arg_liste_montants_recurrent);
			}
		}
		if ($form_type === 'adhesion' || $form_type === 'don+adhesion') {
			// Pour la rétro compatibilité :
			// Avant la v2.x, la liste de valeur pour les adhésions était aussi dans "montant".
			// Donc si type===adhesion (uniquement), et que arg_liste_montants_adhesion vide, on fallback:
			$tmp = $arg_liste_montants_adhesion;
			if ($form_type === 'adhesion' && empty($tmp)) {
				$tmp = $arg_liste_montants;
			}

			$_ajoute_propositions('adhesion', 'unique', 'adhesion', $tmp);
			// TODO: passer par un campagnodon_adhesion_recurrente_active et un $arg_adhesion_recurrente ?
			if ($arg_don_recurrent === '1' && campagnodon_don_recurrent_active()) {
				$_ajoute_propositions('adhesion', 'annuel', 'adhesion_recurrent', $arg_liste_montants_adhesion_recurrent);
			}
		}
	} catch (Throwable $e) {
		spip_log($e->getMessage(), 'campagnodon'._LOG_ERREUR);
		return false;
	}

	return $r;
}

/**
 * Retourne les civilités à proposer dans le formulaire.
 */
function form_init_liste_civilites($mode_options) {
	if (is_array($mode_options) && array_key_exists('liste_civilites', $mode_options)) {
		// Pour des soucis de cohérence dans la config, il faut ici inverser le sens key=>val
		$l = array();
		foreach ($mode_options['liste_civilites'] as $k => $v) {
			$l[$v] = $k;
		}
		return $l;
	}
	return array(
		'M' => 'M.',
		'Mme' => 'Mme.',
		'Mx' => 'Mx.'
	);
}

function form_init_get_adhesion_magazine_prix($mode_options, $form_type) {
	if (
		($form_type === 'adhesion' || $form_type === 'don+adhesion')
		&& is_array($mode_options)
		&& array_key_exists('adhesion_magazine_prix', $mode_options)
		&& ! empty($mode_options['adhesion_magazine_prix'])
	) {
		return intval($mode_options['adhesion_magazine_prix']);
	}
	return 0;
}


function form_init_liste_souscriptions_optionnelles($form_type, $mode_options, $arg_souscriptions_perso) {
	if (!is_array($mode_options) || !array_key_exists('souscriptions_optionnelles', $mode_options)) {
		return array();
	}

	$types_acceptes = [];
	if ($form_type === 'don+adhesion') {
		$types_acceptes[] = 'don';
		$types_acceptes[] = 'adhesion';
	} else {
		$types_acceptes[] = $form_type;
	}

	$r = array();

	$test_so = function (&$so, $test_optionals) use ($types_acceptes) {
		if (!array_key_exists('pour', $so) || empty($so['pour'])) {
			// Pas de condition "pour", on prend toujours
			return true;
		} elseif (is_array($so['pour'])) {
			foreach ($types_acceptes as $type) {
				if (false !== array_search($type, $so['pour'], true)) {
					return true;
				}

				if ($test_optionals) {
					// Dans la conf, on peut mettre "don?":
					// cela veut dire qu'on peut utiliser cette souscription sur le type "don",
					// mais elle ne sera proposée que si on l'a explicitement demandé dans la balise.
					if (false !== array_search($type.'?', $so['pour'], true)) {
						return true;
					}
				}
			}
		}
		return false;
	};

	// 2 modes: soit on prend la config par défaut, soit on a personnalisé via la balise
	if (empty($arg_souscriptions_perso)) {
		foreach ($mode_options['souscriptions_optionnelles'] as $k => $so) {
			if ($test_so($so, false)) { // false, car ici on ne veut que les souscriptions activées par défaut
				$r[$k] = $so;
			}
		}
	} else {
		$souscriptions_perso = explode(',', $arg_souscriptions_perso);
		foreach ($souscriptions_perso as $k) {
			if (!array_key_exists($k, $mode_options['souscriptions_optionnelles'])) {
				continue;
			}
			$so = $mode_options['souscriptions_optionnelles'][$k];
			if (!is_array($so)) {
				continue;
			}
			if ($test_so($so, true)) {
				$r[$k] = $so;
			}
		}
	}
	return $r;
}
