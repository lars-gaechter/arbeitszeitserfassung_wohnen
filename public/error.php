<?php

$codes = [
    '403/' => ['403 Forbidden', 'Kein zugriffsrecht'],
    '404/' => ['404 Not Found', 'Die seite wurde nicht gefunden'],
    '500/' => ['500 Internal Server Error', 'Die verarbeitung der seiten anfrage konnte nicht erfüllt werden'],
    '503/' => ['503 Service Unavailable', 'Ein dienst welchen diese seite brauch ist nicht verfügbar'],
];

if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if (isset($codes[$status])) {
        $title = $codes[$status][0];
        $message = $codes[$status][1];
    } else {
        $title = '?';
        $message = 'Keine nachricht vorhanden für dieses Problem.';
    }
} else {
    $title = 'Kein error';
    $message = 'Diese Seite wurde ohne ein vorheriges problem aufgerufen.';
}
echo
"
<!DOCTYPE html>
<html lang='de'>
    <head>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta charset='UTF-8'>
        <meta name='description' content='Error'>
        <link rel=\"stylesheet\" href=\"/css/main.css\">
        <link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"favicon.ico\" sizes='16x16'>
        <title>$title</title>
    </head>
    <body>
    <div style='max-width: 500px; margin: auto; background: white;'>
        <h1 style='font-size: 36px;'>$title</h1>
        <p style='font-size: 36px;'>$message</p>
      </div>
    </body>
</html>
";
