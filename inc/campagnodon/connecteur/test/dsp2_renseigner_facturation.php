<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Retourne les infos de contact qui ont été soumise à la sauvegarde.
 * C'est utilisé pour le DSP2 de SPIP_BANK.
 * Doit, dans la mesure du possible, retourner nom, adresse, etc...
 * (voir le pipeline campagnodon_bank_dsp2_renseigner_facturation)
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $id_contact_distant
 *  L'id dans le système distant.
 * @param $flux
 *  Le flux du pipeline de spip bank.
 */
function inc_campagnodon_connecteur_test_dsp2_renseigner_facturation_dist($mode_options, $id_contact_distant, &$flux) {
  // FIXME: remonter les infos de la base de donnée.
}
