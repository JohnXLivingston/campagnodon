# Campagnodon

Plugin [SPIP](http://www.spip.net/) pour la gestion des campagnes d'adhésions et de dons.

Ce plugin s'interface avec le plugin [Bank](https://github.com/nursit/bank>), et propose des formulaires d'adhésions et de dons.

Ce plugin a été développé pour les besoins d'[Attac France](https://france.attac.org), en remplacement du plugin [souscription](https://plugins.spip.net/souscription.html).

Contrairement au plugin [souscription](https://plugins.spip.net/souscription.html), le stockage et le traitement des données personnelles se fait dans un système distant, pour des raisons de compartimentation et de sécurisation.
Dans le cas d'[Attac France](https://france.attac.org), ces données sont stockées et traitées via une instance CiviCRM (non accessible publiquement).

Pour l'instant, le code est lié aux API spécifiques développées coté CiviCRM pour les besoins d'Attac. Mais on peut tout à fait imaginer connecter cela à d'autres types d'API.

Pour la liste des versions disponibles, merci de vous référer au [CHANGELOG](CHANGELOG.md).

## Licence

Ce projet est sous licence [AGPLv3 licence](LICENSE).

## Configuration

Pour configurer la connexion avec CiviCRM, ajouter dans le fichier
`config/mes_options.php` de SPIP :

* une constante `_CAMPAGNODON_MODE` avec pour valeur `civicrm`,
* une variable `_CAMPAGNODON_CIVICRM_API_OPTIONS` contenant les arguments à donner au constructeur de [civicrm_api3](inc/civicrm/class.api.php),
* une variable `_CAMPAGNODON_CIVICRM_PREFIX` qui contient le prefix a utiliser pour les ID de transactions créés dans CiviCRM. Cela permet de différencier les différentes plateformes de développement/test/production.
* une variable **optionnelle** `_CAMPAGNODON_PAYS_DEFAULT` avec le code ISO du pays à renseigner par défaut pour l'adresses des contacts. Si non fourni, le champs sera vide par défaut.
* une variable **optionnelle** `_CAMPAGNODON_LISTE_CIVILITE` avec la liste des civilités, et leur valeur dans le système distant. Si non fourni, on part sur une liste par défaut: M/Mme/Mx.

```php
define('_CAMPAGNODON_MODE', 'civicrm');
define('_CAMPAGNODON_CIVICRM_API_OPTIONS', [
        'server' => 'https://civicrm-instance.tld',
        'api_key' => "xxxxx",
        'key' => "xxxxxx"
]);
define('_CAMPAGNODON_CIVICRM_PREFIX', 'campagnodon');
define('_CAMPAGNODON_PAYS_DEFAULT', 'FR');
define('_CAMPAGNODON_LISTE_CIVILITE', array(
        'M' => 'M.',
        'Mme' => 'Mme.',
        'Mx' => 'Mx.'
));
```

## Synchroniser les données distantes

Dans le cas où Campagnodon est lié à un système distant (ex: CiviCRM), il y a des données à synchroniser.
La tâche planifiée `campagnodon_synchronisation_campagnes` tourne toutes les heures.
Pour pouvoir utiliser Campagnodon immédiatement, il suffit d'aller la déclencher à la main dans la page d'administration `Maintenance > Liste des travaux` de SPIP.
