# Changelog

## 2.0.1-RC2

* Fix: les "souscriptions optionnelles" optionnelles (avec un ? dans la conf) étaient mal validées. (#39)
* Pour le formulaire don+adhésion, on active les dons par défaut. (#38)
* Fix typo "aux magazine". (#40)
* Prises en compte des remarques sur le ticket [#41](https://code.globenet.org/attacfr/campagnodon/-/issues/41).

## 2.0.0-RC1

Attention: c'est version est à considérer comme une Release Candidate, elle pourrait ne pas être stable.

Cette version 2.0.0 contient des changements fonctionnels. À l'heure où elle est publiée, je n'ai connaissance
que d'une seule structure utilisant Campagnodon, et les modifications sont faites à la demande de celle-ci.

### Nouvelles fonctionnalités, et changements majeurs

* Nouveau formulaire mixte don + adhésion.
* Adhésions à prix libre.
* Attention: il n'est plus possible de faire un don libre en plus de l'adhésion, cela est jugé redondant avec l'adhésion à prix libre.
* Renouvellement automatique des adhésions (mensuel ou annuel).
* Magazine: avec les adhésions à prix libre, si le montant de l'adhésion est inférieur au prix du magazine, on garde 1€ pour l'adhésion, et le reste pour le magazine.

### Autres modifications

* Refonte complète du code du formulaire, pour simplifier les cas d'usages existants.
* Quelques fix mineurs.
* Nouveaux paramètres _CAMPAGNODON_ADHESION_MINIMUM et _CAMPAGNODON_ADHESION_MAXIMUM

## 1.7.0

* Compatibilité SPIP 3.2 et 4.2.
  * Utilisation de CSS "vanilla", en lieu et place de LESS ou SCSS (problèmes de compatibilité bootstrap 2 et 4 sinon).
  * Les dépendances deviennent ouvertes sur les nouvelles versions.
  * N'utilise plus les classes CSS bootstrap.
  * Suppression des dépendances bootstrap, lesscss/scssphp.
* Fix CSS: on ne touche pas à la largeur des champs date.
* Boutons d'action backend: ajout de "noscroll" sur les boutons "ajax".
* Refonte de l'écran «liste des transactions»: le tableau était trop large, le menu extra se mettait par dessus.
  * passage en page «pleine_largeur»
  * suppression du menu gauche (transactions/campagnes), pour être remplacé par 2 entrées dans le menu SPIP
  * déplacement du bouton de la fonction de migration des dons mensuels depuis le plugin Souscription

## 1.6.1

* On autorise Z-Core 3.0.* dans les dépendances.

## 1.6.0

* Pour les échéances de paiements récurrents, on passe la date de paiement prévu (qui peut être dans le futur).

## 1.5.2

* Fix régression introduite avec les dons récurrents: on pouvait saisir un montant libre sans cliquer sur «montant libre».
* Fix migration, quand on requête spip_souscriptions_liens, il faut que objet='transaction'.

## 1.5.1

* Espace avant le symbole €.

## 1.5.0

* Bandeau d'avancement des campagnes.

## 1.4.4

* Tentative de faire marcher attac_widgets (bis).

## 1.4.3

* Tentative de faire marcher attac_widgets.

## 1.4.2

* Fix niveau de log dans les pipelines de paiement récurrents, quand la transaction n'est pas liée à campagnodon.

## 1.4.1

* _CAMPAGNODON_TRANSMETTRE_PARAMETRES remplacé par _CAMPAGNODON_GERER_WIDGETS.

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
