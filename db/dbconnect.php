<?php
global $dbcon;
$_config = parse_ini_file('../config.ini');
switch ($_config['DB_TYPE']) {
    case 'sqlite':
        $dbcon = new PDO('sqlite:'.$_config['DB_NAME']);
        break;
    case 'mysql':
        $dbcon = new PDO('mysql:dbname='.$_config['DB_NAME'].';host='.$_config['DB_HOST'].';', $_config['DB_USER'], $_config['DB_PASSWORD']);
        break;
    default:
        die('DB Connection not correct !!');
}
