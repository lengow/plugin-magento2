<?php

/*
 * New Translation system base on YAML files
 * We need to edit yml file for each languages
 * /tools/yml/en.yml
 * /tools/yml/fr.yml
 * /tools/yml/es.yml
 * /tools/yml/it.yml
 *
 * Execute this script to generate files
 *
 * Installation de YAML PARSER
 *
 * sudo apt-get install php5-dev libyaml-dev
 * sudo pecl install yaml
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);

$directory = dirname(dirname(__FILE__)).'/tools/yml/';
$listFiles = array_diff(scandir($directory), array('..', '.', 'index.php'));
$listFiles = array_diff($listFiles, array('en_GB.yml'));
array_unshift($listFiles, "en_GB.yml");

// Get Default value
$defaultFiles = ['en_GB.yml', 'log.yml'];
$defaultValues = [];
foreach ($defaultFiles as $defaultFile) {
    foreach (yaml_parse_file($directory.$defaultFile) as $categories) {
        $result = writeCsv($categories, null, true);
        $defaultValues = array_merge($defaultValues, $result);
    }
}

// Write csv file
foreach ($listFiles as $list) {
    $ymlFile = yaml_parse_file($directory.$list);
    $locale =  basename($directory.$list, '.yml');
    if ($list == 'log.yml') {
        $fp = fopen(dirname(dirname(__FILE__)).'/i18n/en_GB.csv', 'a+');
    } else {
        $fp = fopen(dirname(dirname(__FILE__)).'/i18n/'.$locale.'.csv', 'w+');
    }
    // Write translation files
    foreach ($ymlFile as $language => $categories) {
        writeCsv($categories, $fp, false, $defaultValues);
    }
    fclose($fp);
}

function writeCsv($text, $fp = null, $getArray = false, $defaultValues = [], &$frontKey = [])
{
    $values = [];
    if (is_array($text)) {
        foreach ($text as $k => $v) {
            $frontKey[]= $k;
            $result = writeCsv($v, $fp, $getArray, $defaultValues, $frontKey);
            if ($getArray) {
                $values = array_merge($values, $result);
            }
            array_pop($frontKey);
        }
    } else {
        if ($getArray) {
            $values[join('.', $frontKey)] = $text;
        } else {
            $line = '"'.$defaultValues[join('.', $frontKey)].'","'.$text.'"'.PHP_EOL;
            fwrite($fp, $line);
        }
    }
    if ($getArray) {
        return $values;
    }
}