+++
title="Campagnes"
chapter=false
weight=10
+++

Chaque don ou adhésion doit être associé à une campagne.

Les campagnes sont à créer sur le système distant. Ici par exemple, les campagnes crées dans CiviCRM:

![Campages CiviCRM](./images/civicrm_campagnes.png?classes=shadow,border&height=400px)

Ces campagnes sont ensuite synchronisées dans SPIP toutes les heures.
On peut retrouver la liste des campagnes dans **l'espace privé** de SPIP, via le menu
`Activité > Campagnodon > Campagnes` :

![Campagnes synchronisées dans SPIP](./images/spip_campagnes.png?classes=shadow,border&height=400px)

{{% notice info %}}
  Si vous ne voulez pas attendre que la synchronisation passe, vous pouvez la déclencher manuellement via
  le menu SPIP `Maintenance > Liste des travaux`, puis en cherchant la
  **Tâche CRON campagnodon_synchronisation_campagnes (toutes les 3600 s)**. Il suffit alors de cliquer sur
  `Exécuter maintenant`.
{{% /notice %}}

{{% notice info %}}
  Campagnodon peut être lié à plusieurs systèmes distant différents.
  La colonne `origine` indique le nom du système distant concerné.
{{% /notice %}}
