<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Converti le type de contribution.
 * @param $mode_options
 *  Les options venant de _CAMPAGNODON_MODES
 * @param $idx
 * La référence externe de la transaction
 * @param $nouveau_type
 * Le nouveau type.
 * @param $parametres_api
 * Des paramètres additionnels à passer à l'API.
 */
function inc_campagnodon_connecteur_test_convertir_dist($mode_options, $idx, $nouveau_type_distant, $parametres_api) {
  return array(
    'operation_type' => $nouveau_type_distant
  );
}
