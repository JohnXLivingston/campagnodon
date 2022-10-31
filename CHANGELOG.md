# Changelog

## 1.4.0

* Paramètre optionnel _CAMPAGNODON_TRANSMETTRE_PARAMETRES.
* Modifications sur le script de migration des dons récurrents.
* Modification du pipeline d'abonnement pour être compatible avec la modification sur la migration.
* Fix warnings sur la synchronisation des campagnes.

## 1.3.2

* On empêche de soumettre rapidement 2 fois le formulaire (pour bloquer les doubles clicks).

## 1.3.1

* Par soucis de clarification fonctionnelle, on déclenche une synchro juste après la création d'une transaction.

## 1.3.0

* Simplification de l'API de récurrence: c'est le système d'origine qui spécifie le montant, on ne cherche plus à dériver de la transaction parent.

## 1.2.0

* Migration des dons récurrents du plugin Souscription.

## 1.1.1

* Fix typo.

## 1.1.0

* Gestion du type de paiement SEPA, en se basant sur refcb (SPIP Bank).

## 1.0.5

* Fix limite min des dons + message d'erreur mieux adapté.

## 1.0.4

* Paramètre pour définir les dons min et max autorisés.

## 1.0.3

* Maj mineure.

## 1.0.2

* Option pour définir la date de début des «abonnements» (dons récurrents).

## 1.0.1

* idx: option idx_id_length pour spécifier le nombre de chiffres minimum à utiliser.
* Fix: l'idx pour les transactions «don_mensuel_echeance» n'utilisait pas le bon préfixe.

## 1.0.0

* Dons récurrents.

## 0.1.4

* Fix libellé du bouton submit du formulaire d'adhésion.
* Formulaire d'adhésion: on ne met qu'une phrase d'explication pour la déduction des impôts.

## 0.1.3

* Fix dépendance manquante à lesscss.

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
