# Changelog

## 0.1.2

* Fix régression montant libre sur les dons.
* Fix calcul déduction des impôts, et grammaire du message.
* Mise en forme de la page de paiement.

## 0.1.1

* Fix typo dans une phrase

## 0.1.0

* Dons récurrents WIP (développement mis en pause).
* Générateur de balise Campagnodon: hauteur du champs, et ajout de retours à la ligne pour plus de lisibilité.

## 0.0.14

* Dsp2: on retourne aussi le téléphone.

## 0.0.7

* Cette version est juste là pour garder la même version que le plugin Civicrm.

## 0.0.6

* Souscriptions optionelles: on passe l'attribut «name» à l'API.
* Import des campagnes: on passe par une API dédiée (sinon on a un problème de droits).
* Fix des feuilles de styles, notamment pour les petits écrans.

## 0.0.5

* Conversions adhésions/dons.
* Fix: le type d'opération pour les abonnements magazines doit être différent que pour l'adhésion.
* Adresse postale: découpage en 3 champs.
* Fix connecteur dsp2 civicrm. Refactoring du connecteur, pour plus de cohérence avec les autres connecteurs.
* On stocke le statut distant des transactions.
* Fix balises html fermantes manquantes.
* Fix: pas d'erreur quand un connecteur n'est pas trouvé.

## 0.0.4

* Page dédiée pour la transaction.
* Le lien vers la page d'une transaction est transmis au système distant.
* Recherche dans la liste des transactions et la liste des campagnes.
* Fix mineurs.
* Ajout du champs `operation_type` dans l'appel d'API Start.
* Ajout d'un paramètre «source» que l'on peut configurer. Sera utilisé pour les créations de contacts, contributions et adhésions.

## 0.0.3

* Gestion des Adhésions, et divers WIP.

## 0.0.2

* WIP

## 0.0.1

* Première version de ce plugin.
