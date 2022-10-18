+++
title="CiviCRM"
chapter=false
+++

Le présent document indique comment installer un environnement de développement/test conforme à ce qu'utilise Attac.

## Installation de CiviCRM

### buildkit

CiviCRM fourni l'outils [buildkit](https://docs.civicrm.org/dev/en/latest/tools/buildkit/) qui met à disposition un ensemble d'outils pour faciliter le déploiement d'instances CiviCRM sur une machine de développement.

La première étape est donc d'installer buildkit sur la machine de développement (ou dans une machine virtuelle).

Pour cela, suivre la documentation d'installation de [buildkit](https://docs.civicrm.org/dev/en/latest/tools/buildkit/#installation).

### Déployer une instance CiviCRM

CiviCRM doit être associé à un CRM. Chez Attac, il s'agit de Drupal 7.
À l'heure où cette documentation est écrite, CiviCRM est dans la version 5.47.

On peut aisément utiliser l'utilitaire [civibuild](https://docs.civicrm.org/dev/en/latest/tools/civibuild/) pour créer une instance CiviCRM vierge. Pour cela, se placer dans le dossier où l'on a intallé buildkit, puis:

```bash
./bin/civibuild create dev_attac --type drupal-clean --url http://127.0.0.1:9900 --civi-ver  5.47
```

Dans la ligne ci-dessus, on choisi d'installer CiviCRM sur localhost, et le rendre accessible via le port 9900.
Adaptez ceci à votre situation.

Les identifiants pour s'y connecter seront affiché dans la console.

Note: ce type d'installation vient avec des données d'exemple.

### Configurations spécifiques de CiviCRM

Le mode de fonctionnement pour Attac suppose les configurations ci-dessous.

1) activer le composant «civicampaign» (`Administrer > Paramètres système > Composants`)
2) installer l'extension [proca](https://github.com/fixthestatusquo/proca-civicrm/) (télécharger dans le dossier des extensions, puis activer dans `Administrer > Paramètres système > Extensions`)

### Installer l'extension CiviCRM Campagnodon

Allant de paire avec Campagnodon, il y a une Extension CiviCRM [campagnodon-civicrm](https://code.globenet.org/attacfr/campagnodon-civicrm).

Placer les fichiers de l'extension dans le dossier des extensions du CiviCRM, puis aller dans l'interface d'administration `Administrer > Paramètres système > Extension` pour l'activer.

Il faut ensuite obtenir des clés d'API CiviCRM, et les configurer coté SPIP.
Pour obtenir les clés, on pourra par exemple installer l'extension `API Key Management`.
Ensuite, aller sur la fiche contact de l'utilisateur admin qui servira (ce sont ses droits qui seront appliqués), ouvrir l'onglet «API Key», générer une nouvelle clé d'API. La clé de site, aussi nécessaire, est affichée sur le même écran.
