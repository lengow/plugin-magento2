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
    find $DIRECTORY -name $FILE -nowarn -exec rm -rf {} \;
    echo "- Delete $FILE : ""$VERT""DONE""$NORMAL"""
}

remove_directories(){
    DIRECTORY=$1
    find $DIRECTORY -maxdepth 1 -mindepth 1 -type d -exec rm -rf {} \;
    echo "- Delete $FILE : ""$VERT""DONE""$NORMAL"""
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

# variables
FOLDER_TMP="/tmp/app/code"
FOLDER_TEST="/tmp/app/code/Lengow/Connector/Test"
FOLDER_TOOLS="/tmp/app/code/Lengow/Connector/tools"
FOLDER_ETC="/tmp/app/code/Lengow/Connector/etc"

VERT="\\033[1;32m"
ROUGE="\\033[1;31m"
NORMAL="\\033[0;39m"
BLEU="\\033[1;36m"

# process
echo
echo "#####################################################"
echo "##                                                 ##"
echo "##       ""$BLEU""Lengow Magento""$NORMAL"" - Build Module          ##"
echo "##                                                 ##"
echo "#####################################################"
echo
FOLDER="$(dirname "$(dirname "$(dirname "$(pwd)")")")"
echo $FOLDER
if [ ! -d "$FOLDER" ]; then
	echo "Folder doesn't exist : ""$ROUGE""ERROR""$NORMAL"""
	echo
	exit 0
fi

# generate translations
php translate.php
echo "- Generate translations : ""$VERT""DONE""$NORMAL"""
# create files checksum
php checkmd5.php
echo "- Create files checksum : ""$VERT""DONE""$NORMAL"""
# remove TMP FOLDER
remove_directory $FOLDER_TMP
# create folder
mkdir /tmp/app
# copy files
cp -rRp $FOLDER $FOLDER_TMP
# remove marketplaces.json
remove_files $FOLDER_ETC "marketplaces.json"
# remove dod
remove_files $FOLDER_TMP "dod.md"
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
echo "- Remove Tools folder : ""$VERT""DONE""$NORMAL"""
# remove Test folder
remove_directory $FOLDER_TEST
echo "- Remove Test folder : ""$VERT""DONE""$NORMAL"""
# remove todo.txt
find $FOLDER_TMP -name "todo.txt" -delete
echo "- todo.txt : ""$VERT""DONE""$NORMAL"""
# make zip
cd /tmp
zip "-r" $ARCHIVE_NAME "app"
echo "- Build archive : ""$VERT""DONE""$NORMAL"""
then
    mv $ARCHIVE_NAME ~/Bureau
else 
    mv $ARCHIVE_NAME ~/shared
fi