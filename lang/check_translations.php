<?php
$refLang='en-GB';
$translatedLanguages = array('es-ES', 'de-DE', 'fr-FR', 'it-IT');
$translations = array();
$ref = array();
$refValues = array(); // Store the EN values
$doc = new DOMDocument();
$doc->load("translations.xml");
$xpath = new DOMXpath($doc);

$refElements = $xpath->query("/translations/".$refLang."/*/*");
if (!is_null($refElements)) {
  foreach ($refElements as $element) {
    $key = $element->nodeName.'@'.$element->parentNode->nodeName;
    $ref[] = $key;
    $refValues[$key] = $element->nodeValue; // Store the English text
  }
}

foreach ($translatedLanguages as $lang){
  $langElements = $xpath->query("/translations/".$lang."/*/*");
  $translation = array();
  if (!is_null($langElements)) {
    foreach ($langElements as $element) {
      $translation[] = $element->nodeName.'@'.$element->parentNode->nodeName;
    }
  }
  $notTranslated = array_diff($ref,$translation);
  if( count($notTranslated) ){
    echo "<h1>Not translated for $lang</h1>\n";
    echo "<ul>\n";
    foreach ($notTranslated as $value) {
      echo "<li>$value: <strong>" . htmlspecialchars($refValues[$value]) . "</strong></li>\n";
    }
    echo "</ul>\n";
  }
}
?>