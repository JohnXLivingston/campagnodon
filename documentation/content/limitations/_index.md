+++
title="Limitations connues"
chapter=false
weight=90
+++

## SPIP

### Dépendances obsolètes

Pour faciliter le développement de Campagnodon pour Attac France, nous avons figé
certaines dépendances de plugins dans des versions obsolètes.

De plus, les squelettes ont été conçu pour bootstrap2 (qui est la version utilisée par Attac France).

Si ces dépendances vous posent problèmes, ou si vous souhaitez utiliser une version plus récente de boostrap,
n'hésitez pas à contacter l'auteur, par exemple via l'un des dépots listé dans le menu gauche.

## CiviCRM

### Droits d'accès et extensions tierces

Les appels d'API effectués par Campagnodon sont fait avec des droits restreints.
Si d'autres extensions CiviCRM utilisent des Hooks pour ajouter des actions spécifiques,
il se peut que ces actions fassent échouer les appels d'API (ou est un comportement innatendu),
à cause de droits manquants.

Par exemple, si une extension essaie d'intercepter les changements d'adresse sur des contacts,
pour par exemple ajouter des «relationship», cela risque d'échouer.

Actuellement la seule solution proposée est de modifier les extensions en question pour prendre en compte
ce cas de figure. Par exemple en y désactivant le «check permission» des appels d'API CiviCRM v3 et v4.
