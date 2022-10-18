+++
title="Architecture"
chapter=false
weight=50
+++

## Principes de base

Campagnodon est conçu de sorte à séparer:

* Le **système d'origine**: il s'agit du site public sur lequel les utilisateur⋅rice⋅s pourront effectuer des dons et adhésions
* Le **système distant**: il s'agit du *backend* dans lequel seront traités les données personnelles

{{<mermaid>}}
graph LR;
  Origine[Système d'origine]
  Distant[Système distant]

  Origine --> Distant
  Distant -.-> Origine
{{< /mermaid >}}

À noter que les API utilisées pour communiquer entre les deux systèmes sont (quasiment) unidirectionnelles:
en dehors d'un cas particulier (décrit plus loin), le système d'origine ne peut qu'alimenter le système distant,
mais ne peut en extraire de donnée.
Cela permet de **compartimenter** les données, et permet une meilleure **sécurisation** de celles-ci.

Campagnodon a été originellement conçu pour:

* [SPIP](http://www.spip.net/) comme **système d'origine**
* [CiviCRM](https://civicrm.org/) comme **système distant**

Toutefois, les API utilisées pour la communication sont standardisées, et il est tout à fait possible de changer le système d'origine et/ou le système distant.

Un même système d'origine peut pointer vers plusieurs systèmes distants.
Il n'y a pas forcément de cas d'usage précis pour cela, mais cela permet de simplifier un éventuel changement de système distant.

## Flux

Dans le schéma ci-dessous, nous utiliserons SPIP comme système d'origine et CiviCRM comme système distant pour mieux illustrer.

{{<mermaid>}}
sequenceDiagram
  participant SPIP
  participant CiviCRM
  Note over SPIP: L'utilisateur⋅rice soumet le formulaire de don/adhésion
  SPIP ->> CiviCRM: Création d'une transaction
  Note over SPIP: Choix de la méthode de paiement
  SPIP -->> CiviCRM: Dans certains cas, le prestaire a besoin des données personnelles (DSP2)
  CiviCRM -->> SPIP: Renvoi des données personnelles, uniquement si la transaction est bien en cours
  Note over SPIP: Validation de la méthode de paiement
  loop Statut de la transaction mis à jour par SPIP Bank/le pretataire bancaire
    SPIP ->> CiviCRM: Mise à jour du statut de la transaction
  end
{{</mermaid>}}

En dehors du cas où le prestataire bancaire a besoin de données personnelles pour gérer le protocole DSP2
(voir la [documentation de SPIP Bank](https://contrib.spip.net/Plugin-Bank)), les API fonctionnent donc de manière
uni-directionnelles: SPIP alimente CiviCRM, mais ne peut pas en extraire de donnée personnelle.
