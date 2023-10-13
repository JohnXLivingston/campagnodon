+++
title="Présentation"
chapter=false
weight=5
+++

Campagnodon est un Plugin [SPIP](http://www.spip.net/) pour la gestion des campagnes d'adhésions et de dons.

Ce plugin s'interface avec le plugin [Bank](https://github.com/nursit/bank>), et propose des formulaires d'adhésions et de dons.

Ce plugin a été développé pour les besoins d'[Attac France](https://france.attac.org), en remplacement du plugin [souscription](https://plugins.spip.net/souscription.html).

Contrairement au plugin [souscription](https://plugins.spip.net/souscription.html), le stockage et le **traitement des données personnelles se fait dans un système distant, pour des raisons de compartimentation et de sécurisation**.
Dans le cas d'[Attac France](https://france.attac.org), ces données sont stockées et traitées via une instance CiviCRM (non accessible publiquement).

Pour l'instant, le code est conçu pour répondre aux besoins d'Attac France, et est notamment lié à CiviCRM (voir la [documentation](documentation/civicrm.md)).
Toutefois, le code essaie d'être le plus générique possible, et fait en sorte d'être face à étendre.

{{% notice tip %}}
L'installation et la configuration de Campagnodon peut être compliquée. N'hésitez pas à [me contacter](https://www.john-livingston.fr/spip.php?page=contact) pour un accompagnement.
{{% /notice %}}

Pour la liste des versions disponibles, merci de vous référer au fichier `CHANGELOG.md` présent dans le dépot.
