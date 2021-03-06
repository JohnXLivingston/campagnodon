
/**
 * Met en place la gestion dynamique du formulaire de Campagnodon
 * @param {string} formSelector Le sélecteur jQuery pour trouver le contenu du formulaire
 */
function campagnodon_formulaire(formSelector) {
  const $form = $(formSelector);

  $form.on('click', 'input[type=checkbox][name=adhesion_avec_don]', () => campagnodon_formulaire_adhesion_avec_don($form));
  $form.on('click', 'input[type=checkbox][name=recu_fiscal]', () => campagnodon_formulaire_recu_fiscal($form));
  campagnodon_formulaire_adhesion_avec_don($form, true);
  campagnodon_formulaire_recu_fiscal($form, true);

  $form.on('click', 'input[type=radio][name=montant]', () => {
    campagnodon_formulaire_montant_libre($form, false, true);
    campagnodon_formulaire_explications($form);
  });

  $form.on('keyup', 'input[name=montant_libre]', () => campagnodon_formulaire_explications($form));
  $form.on('change', 'input[name=montant_libre]', () => campagnodon_formulaire_explications($form));
  $form.on('click', 'input[name=montant_libre]', () => campagnodon_formulaire_explications($form));

  $form.on('click', 'input[type=radio][name=montant_adhesion]', () => campagnodon_formulaire_explications($form));
  campagnodon_formulaire_montant_libre($form, true);
  campagnodon_formulaire_explications($form);
}

/**
 * Cette fonction s'occupe de mettre à jour le formulaire en fonction de si on choisi le montant libre ou non.
 * @param {jQuery} $form Le conteneur jQuery du formulaire
 * @param {boolean} permier_appel Si c'est le premier appel à la fonction. Si falsey, c'est un événement suite à un recalcul.
 * @param {boolean} est_click Vrai si c'est un click.
 */
function campagnodon_formulaire_montant_libre($form, premier_appel = false, est_click = false) {
  const $radio_montant_libre = $form.find('input[name=montant][value=libre]');
  if (!$radio_montant_libre.length) {
    // Le montant libre n'est pas activé
    return;
  }
  if ($radio_montant_libre.attr('type') === 'hidden') {
    // Le champ est hidden => il n'y a que du montant libre => il n'y a rien à faire
    return;
  }
  const montant_libre = $radio_montant_libre.is(':checked');
  const $input_montant_libre = $form.find('input[name=montant_libre]');
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
 * Cette fonction s'occupe de mettre à jour le formulaire en fonction de la case à cocher recu_fiscal.
 * À appeler à chaque affichage, et à chaque changement de valeur de la case à cocher.
 * @param {jQuery} $form Le conteneur jQuery du formulaire
 * @param {boolean} premier_appel Si c'est le premier appel à la fonction. Si falsey, c'est un événement suite à un recalcul.
 */
function campagnodon_formulaire_recu_fiscal($form, premier_appel = false) {
  const $cb = $form.find('input[type=checkbox][name=recu_fiscal]');

  if (!$cb.length) {
    // Il n'y a pas de case à cocher recu_fiscal, on considère qu'il est implicitement obligatoire (c'est le cas pour les adhésions)
    return;
  }

  const checked = $cb.is(':checked');

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
 * Cette fonction met à jour le texte explicatif sous le champ «don».
 * @param {jQuery} $form
 */
function campagnodon_formulaire_recu_fiscal_explication($form) {
  let montant_est_libre = false;
  if ($form.find('input[name=montant][type=hidden][value=libre]').length) {
    // Le champ est hidden => il n'y a que du montant libre
    montant_est_libre = true;
  } else if ($form.find('input[type=radio][name=montant][value=libre]:checked').length) {
    montant_est_libre = true;
  }

  let montant;
  if (montant_est_libre) {
    const $montant_libre = $form.find('input[name=montant_libre]');
    montant = $montant_libre.prop('value');
    montant = parseInt(montant);
  } else {
    const $montant = $form.find('input[type=radio][name=montant]:checked');
    if ($montant.length) {
      montant = parseInt($montant.attr('value'));
    }
  }

  const explication = $form.find('[recu_fiscal_explication]');
  if (montant !== undefined && !isNaN(montant)) {
    let text = explication.attr('recu_fiscal_explication');
    text = text.replace(/_MONTANT_/g, montant);
    text = text.replace(/_COUT_/g, Math.round(montant * .34));
    explication.text(text);
    explication.show();
  } else {
    explication.hide();
  }
}

/**
 * Cette fonction met à jour le texte explicatif sur l'adhésion.
 * @param {jQuery} $form
 */
function campagnodon_formulaire_adhesion_explication($form) {
  const $montant_adhesion = $form.find('input[type=radio][name=montant_adhesion]:checked');
  let montant_adhesion;
  if ($montant_adhesion.length) {
    montant_adhesion = parseInt($montant_adhesion.attr('value'));
  }
  $form.find('[adhesion_explication]').each(function () {
    const explication = $(this);
    let adhesion_magazine_prix = parseInt(explication.attr('adhesion_magazine_prix'))
    if (adhesion_magazine_prix === undefined || isNaN(adhesion_magazine_prix)) {
      adhesion_magazine_prix = 0;
    }
    if (montant_adhesion !== undefined && !isNaN(montant_adhesion)) {
      let cout_adhesion = Math.round((montant_adhesion - adhesion_magazine_prix) * .34);
      let restant_adhesion = montant_adhesion - cout_adhesion;

      let text = explication.attr('adhesion_explication');
      text = text.replace(/_MONTANT_ADHESION_/g, montant_adhesion);
      text = text.replace(/_COUT_ADHESION_/g, cout_adhesion);
      text = text.replace(/_MAGAZINE_PRIX_/g, adhesion_magazine_prix);
      text = text.replace(/_RESTANT_ADHESION_/g, restant_adhesion);
      explication.text(text);
      explication.show();
    } else {
      explication.hide();
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
