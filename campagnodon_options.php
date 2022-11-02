<?php
// Compatibilité avec le plugins attac_widgets. Voir la doc.
if (defined('_CAMPAGNODON_GERER_WIDGETS') && _CAMPAGNODON_GERER_WIDGETS === true) {
  $GLOBALS['spip_pipeline']['bank_redirige_apres_retour_transaction'] .= "|campagnodon_redirection_paiement_widget";
  function campagnodon_redirection_paiement_widget($flux) {
    // NB: le if ci-dessous vient du code qui était actif chez Attac. Je ne suis pas sûr de le comprendre.
    if($flux['args']['succes'] == 'wait' and $flux['args']['row']['statut'] == 'attente') {
      $widget_mode = _request('mode', $_GET);
      if ($widget_mode === 'frame') {
        // $flux['data'] : c'est l'url  précédemment calculée pour la redirection.
        // Je vais y modifier page, type et mode:
        $url = $flux['data'] || '';
        $url = parametre_url($url, 'page', ''); // on retire le paramètre
        $url = parametre_url($url, 'page', 'widget'); // on met la bonne valeur
        $url = parametre_url($url, 'type', '');
        $url = parametre_url($url, 'type', 'campagnodon-payer');
        $url = parametre_url($url, 'mode', '');
        $url = parametre_url($url, 'mode', 'frame');
        $flux['data'] = $url;
      }
    }
    return $flux;
  }
}
