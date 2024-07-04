#!/bin/bash
# Build archive for Magento module
# Step :
#     - Remove .DS_Store
#     - Remove .README.md
#     - Remove .idea
#     - Remove tools folder
#     - Remove Test folder
#     - Remove .git Folder and .gitignore

remove_if_exist(){
    if [ -f $1 ]; then
      rm $1
    fi
}

remove_directory(){
    if [ -d "$1" ]; then
        rm -rf $1
    fi
}
remove_files(){
    DIRECTORY=$1
    FILE=$2
    if [ -f "${DIRECTORY}/${FILE}" ]
    then
        find $DIRECTORY -name $FILE -nowarn -exec rm -rf {} \;
        echo -e "- Delete ${FILE} : ${VERT}DONE${NORMAL}"
    fi
    if [ -d "${DIRECTORY}/${FILE}" ]
    then
        rm -Rf ${DIRECTORY}/${FILE}
    fi
}

remove_directories(){
    DIRECTORY=$1
    find $DIRECTORY -maxdepth 1 -mindepth 1 -type d -exec rm -rf {} \;
    echo -e "- Delete ${DIRECTORY} : ${VERT}DONE${NORMAL}"
}
# check parameters
if [ -z "$1" ]; then
	echo 'Version parameter is not set'
	echo
	exit 0
else
	VERSION="$1"
	ARCHIVE_NAME='lengow.magento2.'$VERSION'.zip'
fi
# Check parameters
if [ -z "$2" ]; then
	echo 'Deploy environment is not set: preprod or prod'
	echo
	exit 0
fi
if [ ! -z "$2" ] && [ "$2" == "preprod" ]; then
        ARCHIVE_NAME="preprod__${ARCHIVE_NAME}"
fi

# variables
FOLDER_TMP="/tmp/app/code/Lengow/Connector"
FOLDER_TEST="/tmp/app/code/Lengow/Connector/Test"
FOLDER_TOOLS="/tmp/app/code/Lengow/Connector/tools"
FOLDER_ETC="/tmp/app/code/Lengow/Connector/etc"

VERT="\e[32m"
ROUGE="\e[31m"
NORMAL="\e[39m"
BLEU="\e[36m"
DEPLOY_ENV=$2

# process
echo
echo "#####################################################"
echo "##                                                 ##"
echo -e "##       "${BLEU}Lengow Magento${NORMAL}" - Build Module             ##"
echo "##                                                 ##"
echo "#####################################################"
echo
PWD=$(pwd)
FOLDER=$(dirname ${PWD})
echo ${FOLDER}

if [ ! -d "$FOLDER" ]; then
	echo -e "Folder doesn't exist : ${ROUGE}ERROR${NORMAL}"
	echo
	exit 0
fi
PHP=$(which php8.1)
echo ${PHP}

# Change config for preprod
if [ ! -z "${DEPLOY_ENV}" ] && [ "${DEPLOY_ENV}" == "preprod" ]; then
    sed -i 's/lengow.io/lengow.net/g' ${FOLDER}/Model/Connector.php
    sed -i 's/lengow.local/lengow.net/g' ${FOLDER}/Model/Connector.php
fi
if [ ! -z "${DEPLOY_ENV}" ] && [ "${DEPLOY_ENV}" == "prod" ]; then
    sed -i 's/lengow.net/lengow.io/g' ${FOLDER}/Model/Connector.php
    sed -i 's/lengow.local/lengow.io/g' ${FOLDER}/Model/Connector.php
fi

# generate translations
${PHP} translate.php
echo -e "- Generate translations : ${VERT}DONE${NORMAL}"
# create files checksum
${PHP} checkmd5.php
echo -e "- Create files checksum : ${VERT}DONE${NORMAL}"
# remove TMP FOLDER
if [ -d "${FOLDER_TMP}" ]
then
    rm -Rf ${FOLDER_TMP}
fi
# create folder
if [ -d /tmp/app ]
then
    rm -Rf /tmp/app
fi
mkdir /tmp/app
mkdir /tmp/app/code
mkdir /tmp/app/code/Lengow
mkdir /tmp/app/code/Lengow/Connector

# copy files
cp -rRp ${FOLDER}/. $FOLDER_TMP
# remove marketplaces.json
    remove_files $FOLDER_ETC "marketplaces.json"
# remove dod
remove_files $FOLDER_TMP "dod.md"
# remove php-cs-fixer-cache
remove_files $FOLDER_TMP ".php-cs-fixer.cache"
# remove Readme
remove_files $FOLDER_TMP "README.md"
# remove .gitignore
remove_files $FOLDER_TMP ".gitignore"
# remove .git
remove_files $FOLDER_TMP ".git"
# remove .DS_Store
remove_files $FOLDER_TMP ".DS_Store"
# remove .idea
remove_files $FOLDER_TMP ".idea"
# remove Jenkinsfile
remove_files $FOLDER_TMP "Jenkinsfile"
# clean tools folder
remove_directory $FOLDER_TOOLS
echo -e "- Remove Tools folder : ${VERT}DONE${NORMAL}"
# remove Test folder
#remove_directory $FOLDER_TEST
#echo -e "- Remove Test folder : ${VERT}DONE${NORMAL}"
# remove todo.txt
find $FOLDER_TMP -name "todo.txt" -delete
echo -e "- todo.txt : ${VERT}DONE${NORMAL}"
# make zip
cd /tmp
zip -r ${ARCHIVE_NAME} app
echo -e "- Build archive : ${VERT}DONE${NORMAL}"
if [ -d  "~/Bureau" ]
then
    mv $ARCHIVE_NAME ~/Bureau
else
    mv $ARCHIVE_NAME ~/shared
fi
