<?php

mb_internal_encoding('UTF-8');

function convertAndroidToCsv($sourceFile, $targetDir)
{
    $csvStr = '';
    $translations = new SimpleXMLElement(file_get_contents($sourceFile));
    foreach ($translations->string as $tr) {
        $name = $tr->attributes()[0] . '';
        $csvStr .= '"'. $name .'";"'. str_replace('"', "\\\"", $tr) .'"' . "\n";
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

function convertCsv($sourceFile, $targetDir, $targetPlatform)
{
    $translationStr = '';
    $csvLines = file($sourceFile);
    foreach ($csvLines as $csvLine) {
        $langArray = str_getcsv(trim($csvLine), ';', '"');
        if (mb_strtolower($targetPlatform)=='ios') {
            $translationStr .= '"'. $langArray[0] .'"="'. str_replace('"', "\\\"", $langArray[1])  .'";' . "\n";
        } else {
            $translationStr .= '<string name="'. $langArray[0] .'">'. $langArray[1]  .'</string>' . "\n";
        }
    }
    $targetExt  =  mb_strtolower($targetPlatform)=='ios'?'strings':'xml';
    if ($targetExt=='xml') {
        $translationStr = '<resources>' . "\n" . $translationStr . "\n" . '</resources>';
    }
    $targetFile = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($sourceFile) . '.' . $targetExt;
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
        convertCsv($sourceFile, $targetDir, $targetPlatform);
        break;

    default:
        echo 'Unknown source file extesion "'. $sourceFileExt .'"!';
        exit(255);
        break;
}
