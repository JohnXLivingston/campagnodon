+++
title="Glossaire"
chapter=false
weight=95
+++

Le présent document explicite différents termes liés à Campagnodon.

### Campagne

Une campagne de don et/ou d'adhésion permet de regrouper les dons et adhésions.
Les campagnes sont à créer dans le [système distant](#système-distant).
Voir la [documentation sur les campagnes](../fonctionnel/campagnes).

### Souscriptions optionnelles

Les soucriptions optionnelles sont des cases à cocher (en «opt-in») qui peuvent être ajoutées en fin de formulaire
de don ou d'adhésion.

Vous pouvez par exemple ajouter des choix du type «m'inscrire à la newsletter», «j'accepte d'être démarché par téléphone», ...

Vous pouvez configurer un ensemble de souscriptions optionnelles au niveau du serveur (voir la documentation de configuration).
Ces souscriptions pourront être utilisables sur les formulaires de don et/ou d'adhésion. Pourront être utilisée par défaut,
ou optionnelle (à activer article par article).

### Système distant

Il s'agit du système qui contient la base de donnée des dons et adhésions.

Campagnodon a été initialement créé pour utiliser CiviCRM comme système distant,
mais on peut aisément adapter Campagnodon à d'autres systèmes distants.

{{% notice info %}}
  Si vous souhaitez utiliser Campagnodon avec autre chose que CiviCRM (ou même autre chose que SPIP), n'hésitez pas à contacter
  [John Livingston](https://www.john-livingston.fr). Vous pouvez par exemple passer par les gestionnaires de tickets de l'un des dépots de code
  référencé sur ce site web (voir dans le menu gauche).
{{% /notice %}}

### Système d'origine

Le système d'origine est le site public sur lequel des personnes peuvent réaliser des dons ou des adhésions.

Campagnodon a été initialement créé pour utiliser SPIP comme système d'origine.
