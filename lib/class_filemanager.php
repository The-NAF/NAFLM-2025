<?php

class FileManager
{
    static function getCssDirectoryName() {
        return realpath('./css');
    }

    static function getSettingsDirectoryName() {
        return realpath('./localsettings');
    }
    
    static function getAllCoreCssSheetFileNames() {
        $cssDirectory = FileManager::getCssDirectoryName();
        return glob($cssDirectory . '/stylesheet*.css');
    }
    
    static function writeFile($fileName, $fileContents) {
        $file = fopen($fileName, 'w');
        fwrite($file, $fileContents);
        fclose($file);
    }
    
    static function readFile($fileName) {
        $fileContents = '';
        if (!file_exists($fileName)) {
            return $fileContents;
        }
        $file = fopen($fileName, 'r');
        while($file && !feof($file))
             $fileContents .= fgets($file);
        if ($file) {
            fclose($file);
        }
        return $fileContents;
    }
    
    static function copyFile($fileName, $newName) {
        return copy($fileName, $newName);
    }
}