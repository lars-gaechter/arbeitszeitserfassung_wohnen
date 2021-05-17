<?php
use Symfony\Component\Dotenv\Dotenv;
include_once __DIR__.'/source/autoload.php';
$ENVFile = ".env";
$ContentENV = file_get_contents($ENVFile);
file_put_contents($ENVFile, str_replace("MAINTENANCE=false", "MAINTENANCE=true", $ContentENV));
(new Dotenv())->bootEnv(".env");
if("true" === $_ENV["MAINTENANCE"]) {
    try {
        $dateTime = RIODateTimeFactory::getDateTime();
    } catch (Exception $e) {
    }
    $cronLogFile = "cron_log.txt";
    $contentBeforeExecute = file_get_contents($cronLogFile);
    file_put_contents($cronLogFile, $contentBeforeExecute."start run at ".$dateTime->format('Y-m-d h:i:s')."\r\n");
    activeUsersEndStart($dateTime);
    $contentAfterExecute = file_get_contents($cronLogFile);
    file_put_contents($ENVFile, str_replace("MAINTENANCE=true", "MAINTENANCE=false", $ContentENV));
    file_put_contents($cronLogFile, $contentAfterExecute."stop run at ".$dateTime->format('Y-m-d h:i:s')."\r\n");
}
exit;