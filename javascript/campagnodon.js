
/**
 * Met en place la gestion dynamique du formulaire de Campagnodon
 * @param {string} formSelector Le sélecteur jQuery pour trouver le contenu du formulaire
 */
function campagnodon_formulaire(formSelector) {
  const $form = $(formSelector);

  // On empêche de soumettre le formulaire rapidement 2 fois de suite (pour bloquer les doubles clicks)
  $form.on('click', 'input[type=submit]', function (ev) {
    if ($form.hasClass('campagnodon-prevent-double-click')) {
      ev.preventDefault();
      ev.stopPropagation();
      ev.stopImmediatePropagation();
      return false;
    }
    $form.addClass('campagnodon-prevent-double-click');
    setTimeout(() => {
      $form.removeClass('campagnodon-prevent-double-click');
    }, 500)
  });

  $form.on('click', 'input[type=radio][name="choix_type"]', () => {
    campagnodon_formulaire_choix_type($form, false);
    campagnodon_formulaire_filtre_montants($form);
    campagnodon_formulaire_explications($form);
    campagnodon_formulaire_recu_fiscal($form, false);
  });
  $form.on('click', 'input[type=radio][name="choix_recurrence"]', () => {
    campagnodon_formulaire_filtre_montants($form);
    campagnodon_formulaire_explications($form);
  });
  $form.on('click', 'input[type=radio][name="montant"]', () => {
    campagnodon_formulaire_choix_montant($form, false);
    campagnodon_formulaire_explications($form);
  });
  $form.on('focus', 'input[name=montant_libre]', function () {
    // Quand on rentre dans un montant_libre, on s'assure que le radio est sélectionné
    const $radio = $(this).closest('li').find('input[type=radio][name=montant]');
    if (!$radio.is(':checked')) {
      $radio.prop('checked', true).trigger('click');
    }
  });

  $form.on('keyup', 'input[name=montant_libre]', () => campagnodon_formulaire_explications($form));
  $form.on('change', 'input[name=montant_libre]', () => campagnodon_formulaire_explications($form));
  $form.on('click', 'input[name=montant_libre]', () => campagnodon_formulaire_explications($form));

  $form.on('click', 'input[type=checkbox][name=recu_fiscal]', () => campagnodon_formulaire_recu_fiscal($form, false));

  campagnodon_formulaire_choix_type($form, true);
  campagnodon_formulaire_choix_montant($form, true);
  campagnodon_formulaire_filtre_montants($form);
  campagnodon_formulaire_explications($form);
  campagnodon_formulaire_recu_fiscal($form, true);

  // TODO: ci-dessous l'ancien code. À voir ce qu'on garde ou pas.

  // $form.on('click', 'input[type=checkbox][name=adhesion_avec_don]', () => {
  //   campagnodon_formulaire_adhesion_avec_don($form);
  //   campagnodon_formulaire_explications($form);
  // });
  // $form.on('click', 'input[type=checkbox][name=recu_fiscal]', () => campagnodon_formulaire_recu_fiscal($form));
  // campagnodon_formulaire_adhesion_avec_don($form, true);
  // campagnodon_formulaire_recu_fiscal($form, true);

  // $form.on('click', 'input[type=radio][name=montant]', () => {
  //   campagnodon_formulaire_montant_libre($form, false, true);
  //   campagnodon_formulaire_explications($form);
  // });
  // $form.on('click', 'input[type=radio][name=montant_recurrent]', () => {
  //   campagnodon_formulaire_montant_libre($form, false, true, '_recurrent');
  //   campagnodon_formulaire_explications($form);
  // });

  // $form.on('click', 'input[type=radio][name=don_recurrent]', () => {
  //   campagnodon_formulaire_don_recurrent($form, false); // doit être appelé avant campagnodon_formulaire_montant_libre
  //   campagnodon_formulaire_montant_libre($form, false, true, '');
  //   campagnodon_formulaire_montant_libre($form, false, true, '_recurrent');
  //   campagnodon_formulaire_explications($form);
  // });

  // $form.on('click', 'input[type=radio][name=montant_adhesion]', () => campagnodon_formulaire_explications($form));
  // campagnodon_formulaire_don_recurrent($form, true); // doit être appelé avant campagnodon_formulaire_montant_libre
  // campagnodon_formulaire_montant_libre($form, true);
  // campagnodon_formulaire_montant_libre($form, true, false, '_recurrent');
  // campagnodon_formulaire_explications($form);
}

/**
 * Raffraichi le formulaire en fonction du type d'action sélectionnées (don ou adhésion).
 * @param {jQuery} $form le conteneur jQuery du formulaire
 * @param {*} premier_appel Si c'est le premier appel à la fonction.
 */
function campagnodon_formulaire_choix_type($form, premier_appel = false) {
  const $radio_choix_type = $form.find('input[name=choix_type]:checked:not(:disabled)');
  let selecteur_fieldset_a_desactiver
  let selecteur_fieldset_a_activer
  let selecteur_a_invisibiliser
  let selecteur_a_visibiliser
  if ($radio_choix_type.length === 0) {
    // on déselectionne tout.
    selecteur_fieldset_a_desactiver = 'fieldset[campagnodon_recurrence_pour_type]'
    selecteur_a_invisibiliser = '[uniquement_pour_adhesion],[uniquement_pour_don]'
  } else {
    // Sinon on doit désactiver les radio des fieldset ne correspondant pas au type courant,
    const choix_type = $radio_choix_type.attr('value')
    selecteur_fieldset_a_desactiver = 'fieldset[campagnodon_recurrence_pour_type][campagnodon_recurrence_pour_type!=' + choix_type + ']'
    selecteur_fieldset_a_activer = 'fieldset[campagnodon_recurrence_pour_type=' + choix_type + ']'

    if (choix_type === 'don') {
      selecteur_a_invisibiliser = '[uniquement_pour_adhesion]'
      selecteur_a_visibiliser = '[uniquement_pour_don]'
    } else if (choix_type === 'adhesion') {
      selecteur_a_invisibiliser = '[uniquement_pour_don]'
      selecteur_a_visibiliser = '[uniquement_pour_adhesion]'
    } else {
      selecteur_a_invisibiliser = '[uniquement_pour_adhesion],[uniquement_pour_don]'
    }
  }

  if (selecteur_fieldset_a_desactiver) {
    $form.find(selecteur_fieldset_a_desactiver + ' input[name=choix_recurrence]').each(function () {
      const $radio = $(this);
      $radio.prop('checked', false);
      $radio.attr('disabled', true);
    })
    $form.find(selecteur_fieldset_a_desactiver).hide();
  }
  if (selecteur_fieldset_a_activer) {
    const selecteur_radio_a_activer = selecteur_fieldset_a_activer + ' input[name=choix_recurrence]'
    $form.find(selecteur_radio_a_activer).each(function () {
      const $radio = $(this);
      $radio.attr('disabled', false);
    })
    // Si aucun n'est coché, on coche le premier.
    if ($form.find(selecteur_radio_a_activer).find(':checked').length === 0) {
      // Nb: on va aussi déclencher le onclick, pour raffraichir la suite.
      $form.find(selecteur_radio_a_activer).first().prop('checked', true).trigger('click');
    }
    $form.find(selecteur_fieldset_a_activer).show();
  }

  if (selecteur_a_invisibiliser) {
    $form.find(selecteur_a_invisibiliser).each(function () {
      $(this).hide();
    });
  }
  if (selecteur_a_visibiliser) {
    $form.find(selecteur_a_visibiliser).each(function () {
      $(this).show();
    });
  }
}

/**
 * Raffraichi le formulaire en fonction du montant sélectionnées.
 * @param {jQuery} $form le conteneur jQuery du formulaire
 * @param {*} premier_appel Si c'est le premier appel à la fonction.
 */
function campagnodon_formulaire_choix_montant($form, premier_appel = false) {
  $form.find('.campagnodon-choix-montant label.campagnodon-is-checked').each(function () {
    $(this).removeClass('campagnodon-is-checked');
  });
  $form.find('input[type=radio][name=montant]:checked').each(function () {
    $(this).closest('label').addClass('campagnodon-is-checked');
  });

  // On doit également modifier des attributs du champs montant_libre le cas échéant.
  const $checked_radio = $form.find('input[type=radio][name=montant]:checked:not(:disabled)');
  if ($checked_radio.val() === 'libre') {
    const $montant_libre = $checked_radio.closest('li').find('input[name=montant_libre]');
    $montant_libre.attr('required', true);
    if (!premier_appel) {
      // C'est un click, on met le focus
      $montant_libre.focus();
    }
  } else {
    // ici, on va pas s'embêter, on va tout vider/désactiver
    // (même si c'est peut être déjà fait par campagnodon_formulaire_filtre_montants)
    // Note: on vide la valeur, pour éviter les erreurs de donnée invalide au submit (pour un champ désactivé)
    $form.find('input[name=montant_libre]').each(function () {
      $(this).attr('required', false).val('');
    });
  }
}

/**
 * Filtre les montants affichés en fonction des choix précédents.
 * @param {jQuery} $form le conteneur jQuery du formulaire
 */
function campagnodon_formulaire_filtre_montants($form) {
  const $radio_choix_type = $form.find('input[name=choix_type]:checked:not(:disabled)');
  const $is_choix_recurrence = $form.find('input[name=choix_recurrence]').length > 0; //  ce champ est optionnel
  const $radio_choix_recurrence = $form.find('input[name=choix_recurrence]:checked:not(:disabled)');

  let selecteur_radio_a_desactiver
  let selecteur_radio_a_activer
  if ($radio_choix_recurrence.length === 0 || ($is_choix_recurrence && $radio_choix_recurrence.length === 0)) {
    // Premier cas: je n'ai pas encore rempli les champs: on filtre tout.
    selecteur_radio_a_desactiver = 'input[name=montant]';
  } else {
    // Sinon, on construit le selecteur qui va bien:
    let campagnodon_pour_type = $radio_choix_type.val(); // don ou adhesion
    if ($is_choix_recurrence && $radio_choix_recurrence.val() !== 'unique') {
      // FIXME: gérer d'autres types de récurrence que 'don mensuel' et 'adhesion annuelle' ?
      campagnodon_pour_type += '_recurrent';
    }
    selecteur_radio_a_activer = 'input[name=montant][campagnodon_pour_type=' + campagnodon_pour_type + ']';
    selecteur_radio_a_desactiver = 'input[name=montant][campagnodon_pour_type!=' + campagnodon_pour_type + ']';
  }

  if (selecteur_radio_a_desactiver) {
    $form.find(selecteur_radio_a_desactiver).each(function () {
      const $radio = $(this);
      $radio.prop('checked', false);
      $radio.attr('disabled', true)
      const $li = $radio.closest('li')
      $li.hide();
      $li.find('input[name=montant_libre]').each(function () {
        const $montant_libre = $(this);
        $montant_libre.val('');
        $montant_libre.attr('disabled', true);
        $montant_libre.attr('required', false);
      });
    });
  }
  if (selecteur_radio_a_activer) {
    $form.find(selecteur_radio_a_activer).each(function () {
      const $radio = $(this);
      $radio.attr('disabled', false)
      const $li = $radio.closest('li')
      $li.show();
      $li.find('input[name=montant_libre]').each(function () {
        const $montant_libre = $(this);
        $montant_libre.attr('disabled', false);
        // Ici on ne le met pas obligatoire, ça ça doit être fait quand on choisi montant=libre.
      });
    });
  }
}

/**
 * Cette fonction s'occupe de mettre à jour le formulaire en fonction de si on choisi le montant libre ou non.
 * @param {jQuery} $form Le conteneur jQuery du formulaire
 * @param {boolean} permier_appel Si c'est le premier appel à la fonction. Si falsey, c'est un événement suite à un recalcul.
 * @param {boolean} est_click Vrai si c'est un click.
 */
function campagnodon_formulaire_montant_libre($form, premier_appel = false, est_click = false) {
  const $radio_montant_libre = $form.find('input[name=montant'+suffix+'][value=libre]');
  if (!$radio_montant_libre.length) {
    // Le montant libre n'est pas activé
    return;
  }
  if ($radio_montant_libre.attr('type') === 'hidden') {
    // Le champ est hidden => il n'y a que du montant libre => il n'y a rien à faire
    return;
  }
  const montant_libre = $radio_montant_libre.is(':checked:not(:disabled)');
  const $input_montant_libre = $form.find('input[name=montant_libre'+suffix+']');
  $input_montant_libre.attr('disabled', !montant_libre);
  $input_montant_libre.attr('required', montant_libre);

  if (montant_libre && est_click) {
    $input_montant_libre.focus();
  }
}

/**
 * Cette fonction s'occupe de mettre à jour le formulaire en fonction de la case à cocher «je souhaite faire un don»
 * (sur le formulaire d'adhésion)
 * @param {jQuery} $form Le conteneur jQuery du formulaire
 * @param {boolean} premier_appel Si c'est le premier appel à la fonction. Si falsey, c'est un événement suite à un recalcul.
 */
function campagnodon_formulaire_adhesion_avec_don($form, premier_appel = false) {
  const $cb = $form.find('input[type=checkbox][name=adhesion_avec_don]');

  if (!$cb.length) {
    // Il n'y a pas de case à cocher adhesion_avec_don, on doit être sur un formulaire de don.
    return;
  }

  const checked = $cb.is(':checked');

  if (premier_appel) {
    // On va noter tous les champs obligatoires, pour pouvoir restaurer la valeur.
    $form.find('[si_adhesion_avec_don] [required]').attr('adhesion_avec_don_obligatoire', true);
  }

  if (checked) {
    // On affiche les zones liées
    $form.find('[si_adhesion_avec_don]').show();
    // On remet les attribut required sur les champs concernés.
    $form.find('[si_adhesion_avec_don] [adhesion_avec_don_obligatoire]').attr('required', true);
  } else {
    // On masque les zones à masquer.
    $form.find('[si_adhesion_avec_don]').hide();
    // On enlève les attributs required des champs concernés.
    $form.find('[si_adhesion_avec_don] [adhesion_avec_don_obligatoire]').removeAttr('required');
  }
}

/**
 * Cette fonction s'occupe de mettre à jour le formulaire en fonction de la case à cocher recu_fiscal et du type.
 * À appeler à chaque affichage, et à chaque changement de valeur de la case à cocher.
 * @param {jQuery} $form Le conteneur jQuery du formulaire
 * @param {boolean} premier_appel Si c'est le premier appel à la fonction. Si falsey, c'est un événement suite à un recalcul.
 */
function campagnodon_formulaire_recu_fiscal($form, premier_appel = false) {
  const $cb = $form.find('input[type=checkbox][name=recu_fiscal]');

  if (!$cb.length) {
    // Il n'y a pas de case à cocher recu_fiscal,
    // on considère qu'il est implicitement obligatoire (c'est le cas pour les formulaire d'adhésion uniquement)
    return;
  }

  const $radio_choix_type = $form.find('input[name=choix_type]:checked:not(:disabled)');

  // Petite particularité: on considère que la case est cochée si on est sur des adhésions.
  const checked = $radio_choix_type.val() === 'adhesion' || $cb.is(':checked');

  if (premier_appel) {
    // On va noter tous les champs obligatoires, pour pouvoir restaurer la valeur.
    $form.find('[si_recu_fiscal] [required]').attr('recu_fiscal_obligatoire', true);
  }

  if (checked) {
    // On affiche les zones liées
    $form.find('[si_recu_fiscal]').show();
    // On remet les attribut required sur les champs concernés.
    $form.find('[si_recu_fiscal] [recu_fiscal_obligatoire]').attr('required', true);
  } else {
    // On masque les zones à masquer.
    $form.find('[si_recu_fiscal]').hide();
    // On enlève les attributs required des champs concernés.
    $form.find('[si_recu_fiscal] [recu_fiscal_obligatoire]').removeAttr('required');
  }
}


/**
 * Cette fonction retourne le montant actuellement choisi.
 * Si pas de montant choisi, retourne null.
 * @param {jQuery} $form Conteneur jQuery du formulaire
 */
function campagnodon_lire_montant($form) {
  const $radio = $form.find('input[type=radio][name=montant]:checked:not(:disabled)');
  if ($radio.length === 0) {
    return null;
  }
  let val = $radio.val();
  if (val !== 'libre') {
    return parseInt(val);
  }
  
  const $montant_libre = $radio.closest('li').find('input[name=montant_libre]:not(:disabled)');
  if ($montant_libre.length === 0) {
    // ne devrait pas arriver, mais bon
    return null;
  }
  val = parseInt($montant_libre.val());
  if (isNaN(val)) {
    return null;
  }
  return val;
}

/**
 * Cette fonction met à jour le texte explicatif sous le champ «don».
 * @param {jQuery} $form
 */
function campagnodon_formulaire_recu_fiscal_explication($form) {
  const montant = campagnodon_lire_montant($form);

  const explication = $form.find('[recu_fiscal_explication]');
  if (explication.length && montant !== null && !isNaN(montant)) {
    let text = explication.attr('recu_fiscal_explication');
    text = text.replace(/_MONTANT_/g, montant);
    text = text.replace(/_COUT_/g, Math.round(montant * .34));
    explication.text(text);
  } else {
    explication.text(''); // on ne hide pas, pour pas être en conflit avec uniquement_pour_don
  }
}

/**
 * Cette fonction met à jour le texte explicatif sur l'adhésion.
 * @param {jQuery} $form
 */
function campagnodon_formulaire_adhesion_explication($form) {
  const montant_adhesion = campagnodon_lire_montant($form);
  $form.find('[adhesion_explication]').each(function () {
    const explication = $(this);
    let adhesion_magazine_prix = parseInt(explication.attr('adhesion_magazine_prix'))
    if (adhesion_magazine_prix === undefined || isNaN(adhesion_magazine_prix)) {
      adhesion_magazine_prix = 0;
    }
    if (montant_adhesion !== null && !isNaN(montant_adhesion)) {
      let montant_don = 0;
      // TODO: est ce qu'on garde le principe des dons en plus de l'adhésion ? Si oui, traiter.
      // if ($form.find('input[type=checkbox][name=adhesion_avec_don]:checked').length) {
      //   montant_don = campagnodon_lire_montant($form);
      //   if (montant_don === null || isNaN(montant_don)) {
      //     montant_don = 0;
      //   }
      // }
      let adhesion_sans_magazine = montant_adhesion - adhesion_magazine_prix + montant_don;
      let cout_adhesion = Math.round(adhesion_magazine_prix + (adhesion_sans_magazine * 0.34));

      let text = explication.attr('adhesion_explication');
      text = text.replace(/_MONTANT_ADHESION_/g, montant_adhesion);
      text = text.replace(/_COUT_ADHESION_/g, cout_adhesion);
      text = text.replace(/_MAGAZINE_PRIX_/g, adhesion_magazine_prix);
      text = text.replace(/_RESTANT_ADHESION_/g, adhesion_sans_magazine);
      explication.text(text);
    } else {
      explication.text(''); // on ne hide pas, pour pas être en conflit avec uniquement_pour_don
    }
  });
}

/**
 * Cette fonction met à jour les différents textes explicatifs (sur les déductions fiscales, etc...)
 * @param {jQuery} $form
 */
function campagnodon_formulaire_explications($form) {
  campagnodon_formulaire_recu_fiscal_explication($form);
  campagnodon_formulaire_adhesion_explication($form);
}

/**
 * Cette fonction gère la bascule entre don unique et don récurrent
 * @param {jQuery} $form Le conteneur jQuery du formulaire
 * @param {boolean} permier_appel Si c'est le premier appel à la fonction. Si falsey, c'est un événement suite à un recalcul.
 */
function campagnodon_formulaire_don_recurrent($form, premier_appel = false) {
  const $radio_don_recurrent = $form.find('input[name=don_recurrent][value=1]');
  if (!$radio_don_recurrent.length) {
    // Le don récurrent n'est pas activé sur ce formulaire
    return;
  }

  let suffix_enabled = '';
  let suffix_disabled = '';
  if ($radio_don_recurrent.is(':checked')) {
    suffix_enabled = '_recurrent';
    $form.find('[si_pas_don_recurrent]').hide();
    $form.find('[si_don_recurrent]').show();
  } else {
    suffix_disabled = '_recurrent';
    $form.find('[si_pas_don_recurrent]').show();
    $form.find('[si_don_recurrent]').hide();
  }
  $form.find('input[name=montant'+suffix_enabled+']').attr('disabled', false).attr('required', true);
  $form.find('input[name=montant'+suffix_disabled+']').attr('disabled', true).attr('required', false);
  // for name=montant_libre and name=montant_libre_recurrent, required and disabled are handled by campagnodon_formulaire_montant_libre
}
