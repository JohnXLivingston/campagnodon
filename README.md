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

### _CAMPAGNODON_MONTANTS

Cette variable **optionnelle** permet de personnaliser les montants par défaut pour les formulaires.

En voici la syntaxe:

```php
define('_CAMPAGNODON_MONTANTS', array(
        'don' => array( // le type de formulaire. 'don' ou 'adhesion'
                '30','50','100','200'
        ),
        'don_recurrent' => array( // NB: ne s'applique que si _CAMPAGNODON_DON_RECURRENT est activé
                '6', '15', '30', '50'
        ),
        'adhesion' => array(
                // une valeur entre [] indique une condition de revenu. 
                // voir les phrases de localisation option_revenu_entre, option_revenu_en_dessous et option_revenu_au_dessus
                '13[-450]',
                '21[450-900]',
                '35[900-1200]',
                '48[1200-1600]',
                '65[1600-2300]',
                '84[2300-3000]',
                '120[3000-4000]',
                '160[4000-]'
        )
));
```

### _CAMPAGNODON_DON_MINIMUM et _CAMPAGNODON_DON_MAXIMUM

Ces variables **optionnelles** permettent de définir un montant minimum et un montant maximum.
Si pas fourni, sera à 1€ et 10000000€.

NB: il est possible de n'en définir qu'une.

```php
define('_CAMPAGNODON_DON_MINIMUM', 5);
define('_CAMPAGNODON_DON_MAXIMUM', 5000);
```

### _CAMPAGNODON_DON_RECURRENT

Pour activer les dons récurrents:

```php
define('_CAMPAGNODON_DON_RECURRENT', true);
```

ATTENTION: votre prestataire de paiement doit être compatible (voir la doc de SPIP Bank).

NB: _CAMPAGNODON_DON_RECURRENT_JOUR ne fonctionne pas comme souhaité. Il semblerait que ce soit une limitation de SPIP Bank.
~~Si vous souhaitez définir une date personnalisée pour les dons récurrents (par ex, chez Attac, ça se fait les 5 du mois),
vous pouvez definir cette variable optionnelle:~~

```php
define('_CAMPAGNODON_DON_RECURRENT_JOUR', 6); // le 5 du mois. NB: un bug non identifié fait que cette valeur va être décrémentée.
```

Dans un **environnement de test**, il est possible d'activer une fonction de debug `déclencher une mensualité`.

Pour cela, ajouter:
```php
define('_CAMPAGNODON_DON_RECURRENT_DEBUG', true);
```

On aura alors un bouton sur chaque transaction parent.
Ce bouton appelle `abos_preparer_echeance` (de SPIP Bank), pour créer une nouvelle mensualité.
À noter que celle-ci n'est pas notée automatiquement comme payée.
Il faut alors passer par les écrans de SPIP Bank pour valider le paiement.

Attention, le processus de don récurrent de SPIP Bank est complexe,
et la procédure ci-dessus n'est pas strictement équivalente à recevoir un paiement récurrent.
Il y a des pipelines qui ne seront pas appelés (voir les FIXME dans le code).

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
                                'type' => 'xxx',
                                'pour' => ['adhesion', 'don?'] // Clé optionnelle. Si présent, permet de spécifier pour quel(s) type(s) de formulaire proposer cette souscriptions optionnelle. Le «?» en suffixe indique qu'elle ne sera dispo que si on l'active explicitement via la balise.
                        ),
                        'comite_local' => array(
                                'label' => 'Me faire connaître à mon Comité Local le plus proche',
                                'type' = 'xxx'
                        ),
                        'participer_actions' => array(
                                'label' => 'Je souhaite participer à des actions',
                                'type' => 'xxx'
                        )
                ),
                'conversion' => array() // voir l'exemple avec CiviCRM pour le format exact.
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
                        'M.' => '2', // NB: '2' doit correspondre à la valeur à utiliser coté CiviCRM. On peut utiliser l'id numérique de la civilité, ou le libellé.
                        'Mme.' => '1',
                        'Mx.' => '3'
                ),
                // `prefix`: préfixe utililisé dans les identifiants Campagnodon. Si on a plusieurs SPIP différents qui pointent sur le même CiviCRM (par ex si on a plusieurs env de test), on pourra utiliser ce préfixe pour différencier ce qui vient des différents sytèmes.
                'prefix' => 'campagnodon',
                // idx_id_length: optionnel. La longueur minimale à utiliser pour les id numériques dans la construction des identifiants Campagnodon.
                'idx_id_length' => 8,
                // `type_contribution`: la correspondance «type de contribution» pour le système distant. Si cette variable est manquante, ou si certains types manquent, ils seront envoyé tel quel au système distant (avec le risque d'être refusé si invalide).
                'type_contribution' => array(
                        'don' => 'Don', // «identifiant campagnodon» => «nom du financial type CiviCRM» (ou ID numérique pour ne pas être dépendant d'un changement de libellé)
                        'adhesion' => 'Cotisation des membres',
                        'adhesion_magazine' => '5',
                        'don_mensuel' => '6',
                        'don_mensuel_echeance' => '6'
                ),
                // `type_paiement`: la correspondance entre le «mode» de SPIP Bank, et le mode de paiement coté CiviCRM
                // S'il manque la valeur courante, elle sera envoyée telle qu'elle à CiviCRM, qui va probablement rejeter la requête.
                'type_paiement' => array(
                        'cheque' => 'Check', // ou l'ID numérique 4
                        'payzen' => 'Debit Card',
                        // Cas particulier: si la clé est de la forme «sepa_xxx», alors si mode=xxx, et refcb commence par SEPA (SPIP Bank),
                        // on utilisera ce mode de paiement ci.
                        'sepa_payzen' => 'SEPA',
                ),
                // `souscriptions_optionnelles` décrit les case à cocher qu'on peut ajouter en fin de formulaire.
                'souscriptions_optionnelles' => array(
                        'newsletter' => array(
                                'label' => 'M\'inscrire sur la liste d\'information d\Attac France',
                                'type' => 'group', // Le type de souscription. Voir coté Campagnodon_CiviCRM ce qui est autorisé (actuellement on n'a que «group»).
                                'cle_distante' => '42', // la clé du groupe (peut être son ID numérique, ou son label)
                                'when' => 'completed', // Quand faire l'ajout dans le groupe. `completed` ou `init` (`init` par défaut).
                                'pour' => ['adhesion', 'don?'] // Clé optionnelle. Si présent, permet de spécifier pour quel(s) type(s) de formulaire proposer cette souscriptions optionnelle. Le «?» en suffixe indique qu'elle ne sera dispo que si on l'active explicitement via la balise.
                        ),
                        'comite_local' => array(
                                'label' => 'Me faire connaître à mon Comité Local le plus proche',
                                'type' => 'opt-in', // ici on est sur un opt-in (du genre accepter les démarchages)
                                'cle_distante' => 'do_not_trade', // le nom de l'opt-in. Doit être une des valeurs codées coté CiviCRM.
                                'when' => 'completed' // Quand faire l'ajout. `completed` ou `init` (`init` par défaut).
                        ),
                        'participer_actions' => array(
                                'label' => 'Je souhaite participer à des actions',
                                'type' => 'tag',
                                'cle_distante' => 26, // id ou libellé du tag
                                'when' => 'completed' // Quand faire l'ajout. `completed` ou `init` (`init` par défaut).
                        ),
                        'magazine_pdf' => array(
                                'label' => 'Je souhaite uniquement recevoir le journal Lignes d’Attac par courriel au format PDF',
                                'pour' => ['adhesion'],
                                'type' => 'special:magazine_pdf', // C'est un type spécial (voir le code spécifique)
                                'cle_distante' => 'custom_21' // le champ où stocker l'info coté CiviCRM (il s'agit d'un champs custom sur les memberships)
                        )
                ),
                'adhesion_magazine_prix' => 12, // Optionnel. Le prix de l'adhésion au magazine. L'adhésion aura alors un prix au moins égal à 12 + 1.
                'adhesion_type' => array(
                        'adhesion' => 1, // Le membership type (id numérique ou libellé)
                        'magazine' => 2 // Le membership type pour le magazine (id numérique ou libellé)
                ),
                // 'libelle_source': Optionnel. Si présent, va servir à calculer la valeur du champs «source» des contacts, des contributions et des adhésions.
                //      Avec les placeholders {ID_CAMPAGNE} et {TITRE_CAMPAGNE} qui seront remplacé par les valeurs adéquates.
                'libelle_source' => 'Depuis le site - #{ID_CAMPAGNE} {TITRE_CAMPAGNE}',
                'conversion' => array(
                        // Ici on peut configurer des conversions d'un type vers un autre.
                        // Les possibilités dépendent du système distant (voir la documentation associée.)
                        'adhesion' => array( // le type contribution de départ.
                                'don' => array( // les types vers lesquels on peut convertir
                                        'statuts_distants' => array('double_membership'), // Les statuts distants pour lesquels on peut convertir.
                                        'parametres_api' => array( // Optionnel. Des parametres additionnels à passer à l'API de conversion. Voir la doc coté CiviCRM.
                                                'convert_financial_type' => [
                                                        'Cotisation des membres' => [
                                                                'new_financial_type' => 'Don',
                                                                'membership' => null
                                                        ],
                                                        '5' => [
                                                                'new_financial_type' => 'Don',
                                                                'membership' => null
                                                        ]
                                                ],
                                                // TODO?
                                                // 'cancel_optional_subscription' => [
                                                //         // Les noms (= la clé) des souscriptions optionnelles à retirer.
                                                //         // Attention, celles-ci ne doivent pas déjà être exécutées. Donc elles doivent avoir 'when'='completed'.
                                                //         // De plus, 'magazine_pdf' est un cas particulier qui ne sera pas pris en compte (mais pas grave, ça ne s'applique pas).
                                                //         'newsletter'
                                                // ]
                                        )
                                )
                        ),
                        // 'don' => array(
                        //         'adhesion' => array(
                        //                 // mêmes clés que plus haut
                        //                 'statuts_distants' => array['completed', ...]
                        //                 ...
                        //         )
                        // )
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

## Personnaliser des libellés

Pour personnaliser des libellés, il suffit de surcharger les fichiers de langues.
Par exemple, créer un fichier `squelettes/lang/local_campagnodon_form_fr.php` avec pour contenu:

```php
<?php
$GLOBALS[$GLOBALS['idx_lang']] = array(
  'j_adhere' => "J'adhère à Attac pour l'année civile en version un montant de :",
);
```
