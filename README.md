# Campagnodon

Plugin [SPIP](http://www.spip.net/) pour la gestion des campagnes d'adhésions et de dons.

Ce plugin s'interface avec le plugin [Bank](https://github.com/nursit/bank>), et propose des formulaires d'adhésions et de dons.

Ce plugin a été développé pour les besoins d'[Attac France](https://france.attac.org), en remplacement du plugin [souscription](https://plugins.spip.net/souscription.html).

Contrairement au plugin [souscription](https://plugins.spip.net/souscription.html), le stockage et le traitement des données personnelles se fait dans un système distant, pour des raisons de compartimentation et de sécurisation.
Dans le cas d'[Attac France](https://france.attac.org), ces données sont stockées et traitées via une instance [CiviCRM](https://civicrm.org/) (non accessible publiquement).

Pour l'instant, le code est conçu pour répondre aux besoins d'Attac France, et est notamment lié à CiviCRM (voir la [documentation](documentation/civicrm.md)).
Toutefois, le code essaie d'être le plus générique possible, et fait en sorte d'être face à étendre.

Pour la liste des versions disponibles, merci de vous référer au [CHANGELOG](CHANGELOG.md).

## Licence

Ce projet est sous licence [AGPLv3 licence](LICENSE).

## Documentation

La documentation est disponible ici:

* https://johnxlivingston.github.io/campagnodon/

Il est également possible de la générer à partir de ce dépot de code, en utilisant [hugo](https://gohugo.io/).
Pour cela, il vous suffit d'installer `hugo`, puis depuis le dossier où vous avez récupéré le code de Campagnodon:

```bash
hugo server -s documentation
```

Puis ouvrez votre navigateur web sur le page `http://localhost:1313`.

## Configuration

Pour configurer la connexion avec CiviCRM, ajouter dans le fichier
`config/mes_options.php` de SPIP les constantes décrites ci-dessous:

### _CAMPAGNODON_MAINTENANCE

Vous pouvez activer un mode maintenance, qui:

* bloquera les formulaires d'adhésion/don, en y affichant un message
* si le formulaire était déjà affiché, et qu'il est soumis, il reviendra avec un message d'erreur demandant d'attendre
* mettra les synchro en attente

```php
defined('_CAMPAGNODON_MAINTENANCE', true); // false pour désactiver le mode maintenance
```

### _CAMPAGNODON_PAYS_DEFAULT

Cette variable **optionnelle** indiquant le code ISO du pays à renseigner par défaut pour l'adresses des contacts. Si non fourni, le champs sera vide par défaut.

```php
define('_CAMPAGNODON_PAYS_DEFAULT', 'FR');
```

### _CAMPAGNODON_GERER_WIDGETS

Cette variable **optionnelle** permet d'activer la compatibilité avec le plugin
[Attac Widgets](https://code.globenet.org/attacfr/attac_widgets/).

Note: ce mode est hautement expérimental, et il est déconseillé d'utiliser ce mode.

```php
define('_CAMPAGNODON_GERER_WIDGETS', true);
```

### _CAMPAGNODON_MONTANTS

Cette variable **optionnelle** permet de personnaliser les montants par défaut pour les formulaires.

En voici la syntaxe:

```php
define('_CAMPAGNODON_MONTANTS', array(
        'don' => array( // le type d'opération: 'don' ou 'adhesion'
                '30','50','100','200'
        ),
        // Note: avant la v2, c'était 'don_recurrent'.
        // Le code sait gérer la rétro compatibilité.
        'don_mensuel' => array( // NB: ne s'applique que si _CAMPAGNODON_DON_RECURRENT est activé
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
        // Note: on peut également ajouter 'adhesion_annuel' et 'adhesion_mensuel'.
        // Si pas configurés, les règles suivantes s'appliquent:
        // - 'adhesion_annuel': reprend la valeur de 'adhesion'
        // - 'adhesion_mensuel': calcule en divisant par 12 adhesion_annuel (ou adhesion),
        //    en arrondissant vers le haut, et en éliminant les valeurs trop petites.
));
```

### _CAMPAGNODON_DON_MINIMUM et _CAMPAGNODON_DON_MAXIMUM

Ces variables **optionnelles** permettent de définir un montant minimum et un montant maximum.
Si pas fourni, sera à 1€ et 10000000€.

NB: il est possible de n'en définir qu'une sur les deux.

```php
define('_CAMPAGNODON_DON_MINIMUM', 5);
define('_CAMPAGNODON_DON_MAXIMUM', 5000);
```

### _CAMPAGNODON_ADHESION_MINIMUM et _CAMPAGNODON_ADHESION_MAXIMUM

Ces variables **optionnelles** permettent de définir un montant minimum et un montant maximum.
Si pas fourni, sera à 5€ et 10000000€.

NB: il est possible de n'en définir qu'une sur les deux.

```php
define('_CAMPAGNODON_ADHESION_MINIMUM', 5);
define('_CAMPAGNODON_ADHESION_MAXIMUM', 5000);
```

### _CAMPAGNODON_DON_RECURRENT, et autres paramètres de récurrence.

#### _CAMPAGNODON_DON_RECURRENT

Pour activer les dons et adhésions récurrent⋅es:

```php
define('_CAMPAGNODON_DON_RECURRENT', true);
```

#### _CAMPAGNODON_RECURRENCES

Vous pouvez éventuellement préciser les récurrences possibles pour chaque type (don/adhésion).
Pour cela, vous pouvez définir `_CAMPAGNODON_RECURRENCES` comme suit:

```php
define('_CAMPAGNODON_RECURRENCES', [
        'don' => ['mensuel'],
        'adhesion' => ['mensuel', 'annuel']
]);
```

Si _CAMPAGNODON_RECURRENCES est manquant, ou si une clé est absente, les valeurs par défaut sont appliquées:

* `'don' => ['mensuel']`
* `'adhesion' => ['mensuel', 'annuel']`

Pour désactiver, utilisez un tableau vide.

ATTENTION: votre prestataire de paiement doit être compatible (voir la doc de SPIP Bank).
Y compris avec les récurrences choisies.

#### _CAMPAGNODON_DON_RECURRENT_JOUR (deprecated)

NB: _CAMPAGNODON_DON_RECURRENT_JOUR ne fonctionne pas comme souhaité. Il semblerait que ce soit une limitation de SPIP Bank.
Cela semble même ne plus être possible avec SPIP Bank v6.
~~Si vous souhaitez définir une date personnalisée pour les dons récurrents (par ex, chez Attac, ça se fait les 5 du mois),
vous pouvez definir cette variable optionnelle:~~

```php
define('_CAMPAGNODON_DON_RECURRENT_JOUR', 6); // le 5 du mois. NB: un bug non identifié fait que cette valeur va être décrémentée.
```

#### _CAMPAGNODON_DON_RECURRENT_DEBUG

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
                                'type' => 'xxx'
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
                                'pour' => ['adhesion', 'don?'], // Clé optionnelle. Si présent, permet de spécifier pour quel(s) type(s) de formulaire proposer cette souscriptions optionnelle. Le «?» en suffixe indique qu'elle ne sera dispo que si on l'active explicitement via la balise.
                                'besoin_adresse' => false // Clé optionnelle. A besoin qu'on ai saisi une adresse (et donc demandé un reçu fiscal)
                        ),
                        'comite_local' => array(
                                'label' => 'Me faire connaître à mon Comité Local le plus proche',
                                'type' => 'opt-in', // ici on est sur un opt-in (du genre accepter les démarchages)
                                'cle_distante' => 'do_not_trade', // le nom de l'opt-in. Doit être une des valeurs codées coté CiviCRM.
                                'when' => 'completed', // Quand faire l'ajout. `completed` ou `init` (`init` par défaut).
                                'besoin_adresse' => true // a besoin qu'on ai saisi une adresse (et donc demandé un reçu fiscal)
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
                'adhesion_magazine_prix' => 12, // Optionnel. Le prix par défaut de l'adhésion au magazine. En cas d'adhésion à un prix inférieur, on garde 1€ pour l'adhésion, le reste pour le magazine (changement de comportement avec la v2.0.0). Si on active les adhésions en paiement mensuel, on divise par 12, et on arrondi vers le haut.
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

## Anti-spam

Campagnodon inclue plusieurs mécanismes optionnels pour tenter de minimiser les SPAMS.

### ALTCHA

Campagnodon inclue la bibliothèque [ALTCHA](https://altcha.org), une alternative libre et sans cookie aux différents types de CAPTCHA.
Cela repose sur une preuve de calcul: le navigateur va devoir effectuer un calcul complexe pour valider le formulaire.
La majorité des bots de spam ne feront pas ces calculs, ce qui devrait limiter la quantité de spam.

Pour activer ce module, il suffit de définir et personnaliser la constante suivante dans le fichier `mes_options.php`:

```php
define('_CAMPAGNODON_ALTCHA', [
        'maxNumber' => 100000, // complexité de la solution à trouver (voir https://altcha.org/fr/docs/complexity/)
        'hmacKey' => 'secret key', // une clé secrète À CHANGER ABSOLUMENT
        'expires' => '5 minute' // la durée de validité d'une solution, sous forme d'une durée PHP valide
]);
```

> Note: concernant la durée de validité de la réponse, si la personne met plus longtemps à soumettre le formulaire, ce n'est pas grave.
> Le code prévoit de recalculer automatiquement.
> Il ne faut toutefois pas mettre une durée trop courte, car si la personne tente de soumettre pendant un calcul, elle sera bloquée quelques instants.

## Synchroniser les données distantes

Dans le cas où Campagnodon est lié à un système distant (ex: CiviCRM), il y a des données à synchroniser.
La tâche planifiée `campagnodon_synchronisation_campagnes` tourne toutes les heures.
Pour pouvoir utiliser Campagnodon immédiatement, il suffit d'aller la déclencher à la main dans la page d'administration `Maintenance > Liste des travaux` de SPIP.

## Ajouter d'autres types de modes

Pour ajouter d'autres modes (et donc d'autres types de système distants), il suffit de créer les fonctions nécessaires, qui seront appelée via la fonction `charger_fonction` de SPIP.
Pour avoir la liste des fonctions nécessaires, voir dans le dossier qui défini les fonctions connecteurs du mode `test` (et remplacer `_test_` par `_montype_` dans les noms de fonctions).

## Personnaliser des libellés

Pour personnaliser des libellés, il suffit de surcharger les fichiers de langues.
Par exemple, créer un fichier `squelettes/lang/campagnodon_form_fr.php` avec pour contenu:

```php
<?php
$GLOBALS[$GLOBALS['idx_lang']] = array(
  'j_adhere' => "J'adhère à Attac pour l'année civile en versant un montant de :",
  'j_adhere_mensuel' => "J'adhère à Attac en versant tous les mois un montant de :",
  'j_adhere_annuel' => "J'adhère à Attac avec un renouvellement automatique annuel pour un montant de :",
);
```

## Migration des dons récurrents initiés sous SPIP Souscription

On peut choisir de migrer des dons qui ont déjà été effectués avec un autre plugin SPIP (ex: Souscription),
Et qui ont déjà été importés sur le système distant.
C'est par ex le cas pour Attac France qui utilisait un script manuel pour importer les dons du plugin Souscription.

Pour activer cette fonctionnalité, il faut le paramètre suivant dans le fichier `config/mes_options.php` de SPIP:

```php
define('_CAMPAGNODON_MIGRATION', [
        // En clé, le nom du plugin à migrer. Pour l'instant on ne supporte que Souscription.
        'souscription' => [
                // En clé, le type de don à migrer. Pour l'instant on ne supporte que les dons récurrents.
                'don_recurrent' => [
                        // Le format de l'idx. On a le droit à certains placeholders:
                        // - {ID_SOUSCRIPTION}
                        // - {ID_TRANSACTION}
                        // - {PAY_ID}
                        // L'idx ainsi calculé va servir à chercher la contribution déjà existante sur le système distant.
                        'idx_format' => 'SPIP/{ID_SOUSCRIPTION}/DonRecur/{ID_TRANSACTION}/{PAY_ID}',
                        'mode' => 'civicrm_1' // le nom du mode à utiliser
                ]
        ]
]);
```

On pourra lancer la migration via un bouton dans l'application Campagnodon.

Une fois la migration faite, pour retirer le bouton, il suffit de commenter la configuration ci-dessus.
