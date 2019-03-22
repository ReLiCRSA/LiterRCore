<?php
include('dbconnect.php');
include('migrate.functions.php');
global $dbcon;
$fileList = [];
$sqlFiles = [];

if (checkMigrationsTable($dbcon)) {
    $fileList = getMigrationFiles();
    $sqlFiles = getRunMigrations($dbcon);
    runMigrations($dbcon, $fileList, $sqlFiles);
}
