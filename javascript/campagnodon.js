
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
    campagnodon_formulaire_souscriptions_optionnelles($form);
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
  campagnodon_formulaire_souscriptions_optionnelles($form);

  // Pour finir, s'il n'y a en tout et pour tout qu'un seul choix pour "choix_recurrence",
  // et que ce choix est coché, on va masquer la ligne.
  if ($form.find('input[name=choix_recurrence]').length <= 1) {
    const $choix_recurrence = $form.find('input[name=choix_recurrence]').first();
    if ($choix_recurrence.is(':checked:not(:disabled)')) {
      // Par sécurité, je vérifie aussi qu'il n'y a pas de message d'erreur.
      const $choix_recurrence_div = $choix_recurrence.closest('.editer_choix_recurrence');
      if ($choix_recurrence_div.find('.erreur_message').length === 0) {
        $choix_recurrence_div.hide();
      }
    }
  }
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
    selecteur_fieldset_a_desactiver = 'fieldset[campagnodon_recurrence_pour_combinaison]'
    selecteur_a_invisibiliser = '[uniquement_pour_adhesion],[uniquement_pour_don]'
  } else {
    // Sinon on doit désactiver les radio des fieldset ne correspondant pas au type courant,
    const choix_type = $radio_choix_type.attr('value')
    selecteur_fieldset_a_desactiver = 'fieldset[campagnodon_recurrence_pour_combinaison][campagnodon_recurrence_pour_combinaison!=' + choix_type + ']'
    selecteur_fieldset_a_activer = 'fieldset[campagnodon_recurrence_pour_combinaison=' + choix_type + ']'

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
    // NB: .find(':checked') ne semble pas marcher, alors que .is(':checked') oui...
    if ($form.find(selecteur_radio_a_activer).filter(function () { return $(this).is(':checked'); }).length === 0) {
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
    let campagnodon_pour_combinaison = $radio_choix_type.val(); // don ou adhesion
    if ($is_choix_recurrence && $radio_choix_recurrence.val() !== 'unique') {
      // FIXME: gérer d'autres types de récurrence que 'don mensuel' et 'adhesion annuelle' ?
      campagnodon_pour_combinaison += '_recurrent';
    }
    selecteur_radio_a_activer = 'input[name=montant][campagnodon_pour_combinaison=' + campagnodon_pour_combinaison + ']';
    selecteur_radio_a_desactiver = 'input[name=montant][campagnodon_pour_combinaison!=' + campagnodon_pour_combinaison + ']';
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
 * Désactive les souscriptions optionnelles non compatibles avec le type d'opération choisi.
 * @param {jQuery} $form
 */
function campagnodon_formulaire_souscriptions_optionnelles($form) {
  const choix_type = $form.find('input[name=choix_type]:checked:not(:disabled)').val();
  $form.find('[campagnodon_souscription_pour]').each(function () {
    const $div = $(this);
    const $cb = $div.find('input[type=checkbox]');
    const pour_attr = $div.attr('campagnodon_souscription_pour');
    let pour = undefined;
    try {
      if (pour_attr) {
        pour = JSON.parse(pour_attr);
        if (!Array.isArray(pour)) {
          pour = undefined;
        }
      }
    } catch {
      pour = undefined;
    }

    let show = false;
    if (pour === undefined) {
      show = true;
    } else {
      if (choix_type) {
        if ((pour.indexOf(choix_type) >= 0) || (pour.indexOf(choix_type + '?') >= 0)) {
          show = true
        }
      }
    }

    if (show) {
      $cb.attr('disabled', false);
      $div.show();
    } else {
      $cb.attr('disabled', true);
      $div.hide();
    }
  });
}
