# Campagnodon

Plugin [SPIP](http://www.spip.net/) pour la gestion des campagnes d'adhésions et de dons.

Ce plugin s'interface avec le plugin [Bank](https://github.com/nursit/bank>), et propose des formulaires d'adhésions et de dons.

Ce plugin a été développé pour les besoins d'[Attac France](https://france.attac.org), en remplacement du plugin [souscription](https://plugins.spip.net/souscription.html).

Contrairement au plugin [souscription](https://plugins.spip.net/souscription.html), le stockage et le traitement des données personnelles se fait dans un système distant, pour des raisons de compartimentation et de sécurisation.
Dans le cas d'[Attac France](https://france.attac.org), ces données sont stockées et traitées via une instance CiviCRM (non accessible publiquement).

Pour l'instant, le code est lié aux API spécifiques développées coté CiviCRM pour les besoins d'Attac. Mais on peut tout à fait imaginer connecter cela à d'autres types d'API.

## Licence

Ce projet est sous licence [AGPLv3 licence](LICENSE).

## Configuration

Pour configurer la connexion avec CiviCRM, ajouter dans le fichier
`config/mes_options.php` de SPIP une constante `_CAMPAGNODON_MODE` avec pour valeur `civicrm`
et une variable `_CAMPAGNODON_CIVICRM_API_OPTIONS` contenant les arguments à donner
au constructeur de [civicrm_api3](inc/civicrm/class.api.php).

```php
define('_CAMPAGNODON_MODE', 'civicrm');
define('_CAMPAGNODON_CIVICRM_API_OPTIONS', [
        'server' => 'https://civicrm-instance.tld',
        'api_key' => "xxxxx",
        'key' => "xxxxxx"
]);
```
