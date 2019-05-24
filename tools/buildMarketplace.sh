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
# Check parameters
if [ -z "$1" ]; then
	echo 'Version parameter is not set'
	echo
	exit 0
else
	VERSION="$1"
	ARCHIVE_NAME='lengow-connector-'$VERSION'.zip'
fi

# Variables
FOLDER_TMP="/tmp/Connector"
FOLDER_TEST="/tmp/Connector/Test"
FOLDER_TOOLS="/tmp/Connector/tools"
FOLDER_ETC="/tmp/Connector/etc"

VERT="\\033[1;32m"
ROUGE="\\033[1;31m"
NORMAL="\\033[0;39m"
BLEU="\\033[1;36m"

# Process
echo
echo "#####################################################"
echo "##                                                 ##"
echo "##       ""$BLEU""Lengow Magento""$NORMAL"" - Build Module          ##"
echo "##                                                 ##"
echo "#####################################################"
echo
FOLDER="$(dirname "$(pwd)")"
echo $FOLDER
if [ ! -d "$FOLDER" ]; then
	echo "Folder doesn't exist : ""$ROUGE""ERROR""$NORMAL"""
	echogit
	exit 0
fi

# Generate translations
php translate.php
echo "- Generate translations : ""$VERT""DONE""$NORMAL"""
# Create files checksum
php checkmd5.php
echo "- Create files checksum : ""$VERT""DONE""$NORMAL"""
#remove TMP FOLDER
remove_directory $FOLDER_TMP
#create folder
mkdir /tmp/app
#copy files
cp -rRp $FOLDER $FOLDER_TMP
# Remove marketplaces.json
remove_files $FOLDER_ETC "marketplaces.json"
# Remove dod
remove_files $FOLDER_TMP "dod.md"
# Remove Readme
remove_files $FOLDER_TMP "README.md"
# Remove .gitignore
remove_files $FOLDER_TMP ".gitignore"
# Remove .git
remove_files $FOLDER_TMP ".git"
# Remove .DS_Store
remove_files $FOLDER_TMP ".DS_Store"
# Remove .idea
remove_files $FOLDER_TMP ".idea"
# Clean tools folder
remove_directory $FOLDER_TOOLS
echo "- Remove Tools folder : ""$VERT""DONE""$NORMAL"""
# Remove Test folder
remove_directory $FOLDER_TEST
echo "- Remove Test folder : ""$VERT""DONE""$NORMAL"""
# Remove todo.txt
find $FOLDER_TMP -name "todo.txt" -delete
echo "- todo.txt : ""$VERT""DONE""$NORMAL"""
# Make zip
cd /tmp
zip "-r" $ARCHIVE_NAME "Connector"
echo "- Build archive : ""$VERT""DONE""$NORMAL"""
mv $ARCHIVE_NAME ~/Bureau