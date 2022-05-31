# Campagnodon

Plugin [SPIP](http://www.spip.net/) pour la gestion des campagnes d'adhésions et de dons.

Ce plugin s'interface avec le plugin [Bank](https://github.com/nursit/bank>), et propose des formulaires d'adhésions et de dons.

Ce plugin a été développé pour les besoins d'[Attac France](https://france.attac.org), en remplacement du plugin [souscription](https://plugins.spip.net/souscription.html).

Contrairement au plugin [souscription](https://plugins.spip.net/souscription.html), le stockage et le traitement des données personnelles se fait dans un système distant, pour des raisons de compartimentation et de sécurisation.
Dans le cas d'[Attac France](https://france.attac.org), ces données sont stockées et traitées via une instance CiviCRM (non accessible publiquement).

Pour l'instant, le code est conçu pour répondre aux besoins d'Attac France, et est notamment lié à CiviCRM (voir la [documentation](documentation/civicrm.md)).
Toutefois, le code essaie d'être le plus générique possible, et fait en sorte d'être face à étendre.

Pour la liste des versions disponibles, merci de vous référer au [CHANGELOG](CHANGELOG.md).

## Licence

Ce projet est sous licence [AGPLv3 licence](LICENSE).

## Configuration

Pour configurer la connexion avec CiviCRM, ajouter dans le fichier
`config/mes_options.php` de SPIP les constantes décrites ci-dessous:

### _CAMPAGNODON_PAYS_DEFAULT

Cette variable **optionnelle** indiquant le code ISO du pays à renseigner par défaut pour l'adresses des contacts. Si non fourni, le champs sera vide par défaut.

```php
define('_CAMPAGNODON_PAYS_DEFAULT', 'FR');
```

### _CAMPAGNODON_MODES

Cette variable décrit le (ou les) modes disponibles pour campagnodon.
En théorie, on ne devrait en définir qu'un.
Toutefois, rien n'empêche d'en définir plusieurs. La clé dans le tableau est le nom qui sera stocké en base de donnée, pour savoir quelle transaction est liée à quel système distant.

Voir les commentaires dans l'exemple ci dessous pour la documentation :

```php
define('_CAMPAGNODON_MODES', array(
        // La clé est la valeur a utiliser pour spécifier le mode quand on appelle le formulaire,
        // et sera également la valeur stockée en base pour retrouver la config associée.
        'test' => array(
                // `test` est un mode qui ne devrait pas être utilisé en production.
                // Il existe essentiellement pour pouvoir tester Campagnodon sans installer de système distant.
                // Avec ce mode, les données seront stockées sous forme de JSON sérialisé dans les tables SPIP.
                // Attention, aucune garanté que le format des données reste cohérent dans le temps.
                'type' => 'test',
                // `liste_civilites`: la liste des civilités, et leur valeur dans le système distant. Si non fourni, on part sur une liste par défaut: M/Mme/Mx.
                'liste_civilites' => array(
                        'M' => 'M.',
                        'Mme' => 'Mme.',
                        'Mx' => 'Mx.'
                ),
                // `souscriptions_optionnelles` décrit les case à cocher qu'on peut ajouter en fin de formulaire.
                'souscriptions_optionnelles' => array(
                        'newsletter' => array(
                                'label' => 'M\'inscrire sur la liste d\'information d\Attac France',
                        ),
                        'comite_local' => array(
                                'label' => 'Me faire connaître à mon Comité Local le plus proche',
                        ),
                        'participer_actions' => array(
                                'label' => 'Je souhaite participer à des actions',
                        )
                )
        ),
        'civicrm_1' => array(
                // `civicrm`: dans ce mode 
                'type' => 'civicrm',
                // `api_options`: arguments à donner au constructeur de [civicrm_api3](inc/campagnodon/connecteur/civicrm/class.api.php)
                'api_options' => [
                        'server' => 'https://civicrm-instance.tld',
                        'api_key' => "xxxxx",
                        'key' => "xxxxxx"
                ],
                // `liste_civilites`: la liste des civilités, et leur valeur dans le système distant. Si non fourni, on part sur une liste par défaut: M/Mme/Mx.
                'liste_civilites' => array(
                        '2' => 'M.', // NB: 'M' doit correspondre à la valeur à utiliser coté CiviCRM. On peut utiliser l'id numérique de la civilité, ou le libellé.
                        '1' => 'Mme.',
                        '3' => 'Mx.'
                ),
                // `prefix`: préfixe utililisé dans les identifiants Campagnodon. Si on a plusieurs SPIP différents qui pointent sur le même CiviCRM (par ex si on a plusieurs env de test), on pourra utiliser ce préfixe pour différencier ce qui vient des différents sytèmes.
                'prefix' => 'campagnodon',
                // `type_contribution`: la correspondance «type de contribution» pour le système distant. Si cette variable est manquante, ou si certains types manquent, ils seront envoyé tel quel au système distant (avec le risque d'être refusé si invalide).
                'type_contribution' => array(
                        'don' => 'Don', // «identifiant campagnodon» => «nom du financial type CiviCRM» (ou ID numérique pour ne pas être dépendant d'un changement de libellé)
                        'adhesion' => 'Cotisation des membres'
                ),
                // `type_paiement`: la correspondance entre le «mode» de SPIP Bank, et le mode de paiement coté CiviCRM
                // S'il manque la valeur courante, elle sera envoyée telle qu'elle à CiviCRM, qui va probablement rejeter la requête.
                'type_paiement' => array(
                        'cheque' => 'Check', // ou l'ID numérique 4
                        'payzen' => 'Debit Card'
                ),
                // `souscriptions_optionnelles` décrit les case à cocher qu'on peut ajouter en fin de formulaire.
                'souscriptions_optionnelles' => array(
                        'newsletter' => array(
                                'label' => 'M\'inscrire sur la liste d\'information d\Attac France',
                                'cle_distante' => 'newsletter' // la clé utilisée dans l'API distante
                        ),
                        'comite_local' => array(
                                'label' => 'Me faire connaître à mon Comité Local le plus proche',
                                'cle_distante' => 'comite_local'
                        ),
                        'participer_actions' => array(
                                'label' => 'Je souhaite participer à des actions',
                                // 'cle_distante' => ???
                        )
                )
        )
));
```

## Synchroniser les données distantes

Dans le cas où Campagnodon est lié à un système distant (ex: CiviCRM), il y a des données à synchroniser.
La tâche planifiée `campagnodon_synchronisation_campagnes` tourne toutes les heures.
Pour pouvoir utiliser Campagnodon immédiatement, il suffit d'aller la déclencher à la main dans la page d'administration `Maintenance > Liste des travaux` de SPIP.

## Ajouter d'autres types de modes

Pour ajouter d'autres modes (et donc d'autres types de système distants), il suffit de créer les fonctions nécessaires, qui seront appelée via la fonction `charger_fonction` de SPIP.
Pour avoir la liste des fonctions nécessaires, voir dans le dossier qui défini les fonctions connecteurs du mode `test` (et remplacer `_test_` par `_montype_` dans les noms de fonctions).
