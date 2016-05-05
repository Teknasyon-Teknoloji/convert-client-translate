<?php

mb_internal_encoding('UTF-8');

function convertAndroidToCsv($sourceFile, $targetDir)
{
    $csvStr = '';
    $xmlStr = file_get_contents($sourceFile);
    $translations = new SimpleXMLElement($xmlStr);
    foreach ($translations->string as $tr) {
        $name = $tr->attributes()[0] . '';
        $csvStr .= '"'. $name .'";"'. str_replace('"', "\\\"", $tr) .'"' . "\n";
    }
    if (strpos($xmlStr, 'string-array')) {
        foreach ($translations->children() as $tr) {
            $name = $tr->attributes()[0] . '';
            foreach ($tr->item as $item) {
                $csvStr .= '"'. $name .'";"'. str_replace('"', "\\\"", $item) .'"' . "\n";
            }
        }
    }
    $targetFile = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($sourceFile) . '.csv';
    file_put_contents($targetFile, $csvStr);
    echo "[INFO] " . $targetFile . " created.\n";
}

function convertiOSToCsv($sourceFile, $targetDir)
{
    $csvStr = '';
    $translations = file($sourceFile);
    foreach ($translations as $tr) {
        $tr = trim($tr);
        if ($tr=='' || substr($tr, 0, 2)=='//') {
            continue;
        }

        if (preg_match('/(.*)"\s?=\s?"(.*)/', $tr, $matches)) {
            $csvStr .= '"'. substr($matches[1], 1) .'";"'. str_replace('"', "\\\"", substr($matches[2], 0, -2)) .'"' . "\n";
        }
    }
    $targetFile = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($sourceFile) . '.csv';
    file_put_contents($targetFile, $csvStr);
    echo "[INFO] " . $targetFile . " created.\n";
}

function convertCsvToiOS($sourceFile, $targetDir)
{
    $translationStr = '';
    $csvLines = file($sourceFile);
    foreach ($csvLines as $csvLine) {
        $langArray = str_getcsv(trim($csvLine), ';', '"');
        $translationStr .= '"'. $langArray[0] .'"="'. str_replace('"', "\\\"", $langArray[1])  .'";' . "\n";
    }
    $targetFile = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($sourceFile) . '.strings';
    file_put_contents($targetFile, $translationStr);
    echo "[INFO] " . $targetFile . " created.\n";
}

function convertCsvToAndroid($sourceFile, $targetDir)
{
    $translationStr   = '';
    $translationArray = array();
    $csvLines = file($sourceFile);
    foreach ($csvLines as $csvLine) {
        $langArray = str_getcsv(trim($csvLine), ';', '"');
        if (isset($translationArray[ $langArray[0] ])===false) {
            $translationArray[ $langArray[0] ] = array();
        }
        $translationArray[ $langArray[0] ][] = $langArray[1];
    }
    foreach ( $translationArray as $key => $trArray) {
        if (count($trArray)>1) {
            $translationStr .= '<string-array name="'. $key .'">'."\n";
            foreach ($trArray as $tr) {
                $translationStr .= '<item>'. $tr .'</item>'."\n";
            }
            $translationStr .= '</string-array>'."\n";
        } else {
            $translationStr .= '<string name="'. $key .'">'. $trArray[0]  .'</string>' . "\n";
        }
    }
    $translationStr = '<resources>' . "\n" . $translationStr . "\n" . '</resources>';
    $targetFile = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($sourceFile) . '.xml';
    file_put_contents($targetFile, $translationStr);
    echo "[INFO] " . $targetFile . " created.\n";
}

$sourceFile = isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:null;
$targetDir  = isset($_SERVER['argv'][2])?$_SERVER['argv'][2]:null;
$targetPlatform = isset($_SERVER['argv'][3])?$_SERVER['argv'][3]:null;

if (!$sourceFile || is_readable($sourceFile)===false) {
    echo '[ERROR] Source file "'. $sourceFile .'" could not found!' ."\n";
    exit(255);
}

if (!$targetDir || is_dir($targetDir)===false || is_writable($targetDir)===false) {
    echo '[ERROR] Target dir "'. $targetDir .'" could not found!' ."\n";
    exit(255);
}

$sourceFileExt = mb_strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));

switch ($sourceFileExt) {
    case 'xml':
        convertAndroidToCsv($sourceFile, $targetDir);
        break;

    case 'strings':
        convertiOSToCsv($sourceFile, $targetDir);
        break;

    case 'csv':
        if (mb_strtolower($targetPlatform)=='ios') {
            convertCsvToiOS($sourceFile, $targetDir);
        } else {
            convertCsvToAndroid($sourceFile, $targetDir);
        }
        break;

    default:
        echo 'Unknown source file extesion "'. $sourceFileExt .'"!';
        exit(255);
        break;
}
