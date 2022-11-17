+++
title="Utilisation dans un article"
chapter=false
weight=20
+++

Pour insérer un formulaire de don et/ou d'adhésion dans un article, il suffit
d'utiliser une balise `<campagnodon>` dans la rédaction de celui-ci.
Il va falloir ajouter des attributs à cette balise, pour configurer le formulaire.

Pas de panique, Campagnodon fourni un utilitaire pour facilement générer la balise adéquate !

## Générer la balise et ses attributs

Commencez par vous rendre dans l'écran des [campagnes](../campagnes) :
dans l'espace privé de SPIP, ouvrez le menu `Activités > Campagnodon`,
puis dans le menu gauche sélectionnez `campagnes` :

![Campagnes synchronisées dans SPIP](./images/spip_campagnes.png?classes=shadow,border&height=400px)

Cliquez ensuite sur la campagne qui vous intéresse.
Une fenêtre s'ouvre alors, reprennant les informations de base de la campagne, ainsi qu'un utilitaire pour
générer la balise qui vous convient :

![Détail d'une campagne](./images/spip_campagne.png?classes=shadow,border&height=400px)

Si vous cochez par exemple le bouton radio `don`:

![Balise pour un formulaire de don](./images/spip_campagne_perso.png?classes=shadow,border&height=400px)

Vous pouvez alors sélectionner et copier/coller la balise campagnodon affichée dans le champs en dessous.

Ici, on a :

```text
<campagnodon|
  origine=civicrm_1|
  id=4|
  type=don
>
```

Ceci est la balise minimale pour un formulaire de don. On y trouve l'identifiant de la campagne, ainsi que
le système distant dont elle vient (dans le cas où votre installation utiliser plusieurs systèmes distants).

Ce formulaire se basera sur la configuration par défaut de votre installation (pour la liste des montants, etc).

Si vous l'insérez dans un article, voilà ce que vous obtiendrez :

![Formulaire de don basique](./images/spip_formulaire_don.png?classes=shadow,border&height=400px)

## Personnalisation de la balise

Vous pouvez cocher les différentes cases de l'utilitaire pour pouvoir personnaliser les différentes options :

![Personnalisation du formulaire de don](./images/spip_campagne_perso_don.png?classes=shadow,border&height=400px)

À chaque fois que vous cochez une option, un nouveau paramètre est ajouté dans la balise.

Ce paramètre reprend les valeurs par défaut de votre installation. **Vous pourrez alors copier/coller la balise
dans un article, puis ensuite changer les valeurs**.

### Don / Adhésion / Objectif

Les premiers boutons radio permettent de choisir le type de formulaire à utiliser :

* formulaire de don
* formulaire d'adhésion (qui permet également de faire un don additionnel)
* objectif (voir plus loin)

### Permettre les dons récurrents

**Si votre prestataire de paiement l'autorise**, vous pouvez activer les dons récurrents sur le formulaire.

{{% notice warning %}}
  Actuellement Campagnodon ne gère que les dons mensuels.
{{% /notice %}}

Voilà à quoi ressemble un formulaire de dons pour lequel les dons récurrents sont activés :

![Formulaire de don avec dons récurrents](./images/spip_formulaire_don_recurrent.png?classes=shadow,border&height=400px)

La personne est d'abord invitée à choisir si elle souhaite donner une seule fois ou plusieurs.

NB: la liste des montants est différente pour les dons uniques et les dons récurrents.

### Montants personnalisés / Montant libre

Par défaut, la liste des montants proposés est paramétrée sur le serveur.
Vous pouvez toutefois changer la liste pour un formulaire donné.

Quand vous cochez cette option, un attribut du type `montants=30,50,70,libre` est ajouté à la balise.

Quand vous copierez cette balise dans votre article, vous pourrez alors modifier la liste des montants proposés.

La liste des montants est tout simplement une ligne de nombre séparés par des virgules (sans espace entre eux).

Vous pouvez ajouter la valeur spéciale `libre` à la liste des montants.
Celle-ci à pour effet d'ajouter une option «montant libre» au formulaire.
L'utilisateur⋅rice pourra alors saisir librement le montant voulu.

Il existe une syntaxe avancée pour les montants :

```text
montants=13[-450],21[450-900],35[900-1200],48[1200-1600],65[1600-2300],84[2300-3000],120[3000-4000],160[4000-]
>
```

Ici certains montants sont accompagnés de bornes entre crochets.
Ces bornes servent à générer une phrase pouvant conditionner par exemple les valeurs à des niveaux de revenus :

![Formulaire d'adhésion](./images/spip_formulaire_adhesion_montants.png?classes=shadow,border&height=400px)

### Personnaliser les souscriptions optionnelles

Les [souscriptions optionnelles](../../glossaire/#souscriptions-optionnelles) peuvent être personnalisées
pour le formulaire.

### Objectif

Si la campagne a un objectif de dons dans le système distant, alors vous aurez la possibilité de générer une
balise `campagnodon_objectif`. En utilisant celle-ci dans un article, vous pourrez afficher une barre de progression.

L'avancement de la campagne est raffraîchi toutes les heures, via le script de synchronisation des campagnes.

{{% notice info %}}
  Si vous souhaitez tester visuellement le rendu des bandeaux, ajoutez `debug_objectif=1` aux paramètres de la balise.
  Vous aurez alors, en plus du bandeau de votre campagne, un rendu de différentes valeurs exemples.
{{% /notice %}}

## Rédaction de l'article

Vous n'avez plus qu'à coller la balise dans un article, puis éventuellement personnaliser les valeurs des attributs :

![Rédaction d'un article](./images/spip_article_redaction.png?classes=shadow,border&height=400px)

Vous pouvez bien sûr remplir l'article avec du texte avant et après la balise.
