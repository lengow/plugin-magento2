# Installation Magento 2 #

## Installation du module ##

### Cloner le repository de Bitbucket dans votre espace de travail ###

Pour Magento 2, le module doit se trouver dans le répertoire de Magento pour fonctionner normalement

    cd ~/Documents/docker_images/magento2/app/code
    git clone git@bitbucket.org:lengow-dev/magento2-v3.git Lengow/Connector
    chmod 777 -R Lengow

### Installation dans Magento 2 ###

    sudo docker exec -t -i $(sudo docker inspect --format="{{.Id}}" magento2_apache_1) /bin/bash
    php bin/magento module:enable Lengow_Connector
    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
    php bin/magento cache:flush
    chmod 777 -R var/

## Traduction ##

Pour traduire le projet il faut modifier les fichiers *.yml dans le répertoire : Documents/modules_lengow/magento2/Lengow/Connector/tools/yml
Attention, dans Magento 2, c'est le contenu anglais qui sert de clé de traduction.
Il faut bien faire attention que la traduction anglaise soit identique dans le code et dans les fichiers yml.

### Installation de Yaml Parser ###

    sudo apt-get install php5-dev libyaml-dev
    sudo pecl install yaml

### Mise à jour des traductions ###

Une fois les traductions terminées, il suffit de lancer le script de mise à jour de traduction pour générer les csv dans le dossier i18n :

    cd ~/Documents/docker_images/magento2/magento/app/code/Lengow/Connector/tools/
    php translate.php

## Versionning GIT ##

1 - Prendre un ticket sur JIRA et cliquer sur Créer une branche dans le bloc développement à droite

2 - Sélectionner en "Repository" lengow-dev/magento2-v3, pour "Branch from" prendre dev et laisser le nom du ticket pour "Branch name"

3 - Créer la nouvelle branche

4 - Exécuter le script suivant pour changer de branche 

    cd ~/Documents/docker_images/magento2/magento/app/code/Lengow/Connector
    git fetch
    git checkout "Branch name"

5 - Faire le développement spécifique

6 - Lorsque que le développement est terminé, faire un push sur la branche du ticket

    git add .
    git commit -m 'My ticket is finished'
    git pull origin "Branch name"
    git push origin "Branch name"

7 - Dans Bitbucket, dans l'onglet Pull Requests créer une pull request

8 - Sélectionner la branche du ticket et l'envoyer sur la branche de dev de lengow-dev/magento2-v3

9 - Bien nommer la pull request et mettre toutes les informations nécessaires à la vérification

10 - Reprendre la liste du Definition of done (dod.md) et vérifier chaques critère et l'insérer dans la description

11 - Mettre tous les Reviewers nécessaires à la vérification et créer la pull request

12 - Lorsque la pull request est validée, elle sera mergée sur la branche de dev

## Commandes Magento 2 ##

Pour utiliser la console Magento, se rendre directement dans l'image docker

    sudo docker exec -t -i $(sudo docker inspect --format="{{.Id}}" magento2_apache_1) /bin/bash

### Principales commandes ###

Activer un module

    php bin/magento module:enable Lengow_Connector

Activer un module en nettoyant les fichiers statiques de Magento

    php bin/magento module:enable --clear-static-content Lengow_Connector

Désactiver un module

    php bin/magento module:disable Lengow_Connector

Voir le mode en cours

    php bin/magento deploy:mode:show

Passer en mode Developer

    rm -rf /var/di/* /var/generation/*
    php bin/magento deploy:mode:set developer

Passer en mode Production

    php bin/magento deploy:mode:set production

Passer en mode Production sans compilation

    php bin/magento deploy:mode:set production --skip-compilation

Compiler Magento

    php bin/magento setup:di:compile

Voir l'état des caches Magento

    php bin/magento cache:status

Vider le cache storage

    php bin/magento cache:flush

Vider le cache de Magento

    php bin/magento cache:clean

Mettre à jour la liste des modules 

    php bin/magento setup:upgrade

Mettre à jour le schéma de base de données du module

    php bin/magento setup:db-schema:upgrade

Mettre à jour les fichiers statiques de Magento

    php bin/magento setup:static-content:deploy

Remettre les droits sur le dossier var

    chmod 777 -R var/
