<?php
/**
 * Handle the errors thrown in here rather than front end output
 *
 * @param $errNo
 * @param $errStr
 * @param $errFile
 * @param $errLine
 */
function errorHandler($errNo, $errStr, $errFile, $errLine)
{
    switch ($errNo) {
        case E_NOTICE:
        case E_USER_NOTICE:
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
        case E_STRICT:
            $errorLevel ="NOTICE";
            break;

        case E_WARNING:
        case E_USER_WARNING:
            $errorLevel ="WARNING";
            break;

        case E_ERROR:
        case E_USER_ERROR:
            $errorLevel ="FATAL";
            break;
        default:
            $errorLevel ="UNKNOWN";
    }
    $logHandle = fopen(dirname(dirname(__FILE__))."/logs/error.log", "a+");
    $dateFormat = '['.date("Y-m-d H:i:s").'] ';
    fputs($logHandle, $dateFormat.$errorLevel.' - '.$errFile.':'.$errLine."\r\n");
    fputs($logHandle, $dateFormat.$errStr."\r\n");
    fclose($logHandle);
}
