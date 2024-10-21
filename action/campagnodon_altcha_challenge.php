<?php

/**
 * @plugin     Campagnodon
 * @copyright  2024
 * @author     John Livingston
 * @licence    AGPL-v3
 */

if (!defined('_ECRIRE_INC_VERSION')){
	return;
}

require_once __DIR__ . '/../vendor/autoload.php';
use AltchaOrg\Altcha\ChallengeOptions;
use AltchaOrg\Altcha\Altcha;


/**
 * Créé un nouveau challenge Altcha (si altcha est actif), et retourne le JSON correspondant.
 */
function action_campagnodon_altcha_challenge_dist() {
  include_spip('inc/campagnodon.utils');
  $altcha_options = campagnodon_altcha_configuration();
  if (!$altcha_options) {
    http_response_code(403);
    die('Forbidden');
  }

  // Create a new challenge
  $expires = new DateTime();
  $expires->add(DateInterval::createFromDateString($altcha_options['expires']));

  $options = new ChallengeOptions([
      'hmacKey'   => $altcha_options['hmacKey'],
      'maxNumber' => $altcha_options['maxNumber'], // the maximum random number
      'expires' => $expires
  ]);
  
  $challenge = Altcha::createChallenge($options);

  header("Content-type: application/json; charset=utf-8");
  echo json_encode($challenge);
}
