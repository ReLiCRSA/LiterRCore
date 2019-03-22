<?php
function checkMigrationsTable($dbcon)
{
    echo "Checking migrations table ...\r\n";
    $statement = $dbcon->query("select 1 from `migrations`;");
    if ($statement == false) {
        $sql = "CREATE TABLE `migrations` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `sqlfile` VARCHAR(255) NOT NULL,
                  `created_at` DATETIME NOT NULL,
                   PRIMARY KEY (`id`))";
        $statement = $dbcon->query($sql);
        if ($statement === false) {
            return false;
        }
        echo "Migrations table created ...\r\n";
    }
    return true;
}

function getMigrationFiles()
{
    echo "Getting file list for migrations ...\r\n";
    $dirList = scandir(dirname(__FILE__)."/migrations");
    $dirList = array_filter($dirList, function ($value) {
        if (in_array($value, ['.','..','.gitkeep'])) {
            return false;
        }
        return true;
    });
    return $dirList;
}

function getRunMigrations($dbcon)
{
    echo "Getting list of migrations run ...\r\n";
    $sql = "SELECT `sqlfile` FROM `migrations`";
    $statement = $dbcon->query($sql);
    $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
    $runSql = [];
    foreach ($results as $result) {
        $runSql[] = $result['sqlfile'];
    }
    return $runSql;
}

function runMigrations($dbcon, $fileList, $sqlFiles)
{
    echo "Running migrations ...\r\n";
    $toRun = array_diff($fileList, $sqlFiles);
    foreach ($toRun as $nextRun) {
        echo "Running - ".$nextRun;
        $sqlToRun = file_get_contents(dirname(__FILE__)."/migrations/".$nextRun);
        $statement = $dbcon->query($sqlToRun);
        if ($statement !== false) {
            $sql = "INSERT INTO `migrations` (`sqlfile`,`created_at`) VALUES (:sqlfile, CURRENT_TIMESTAMP);";
            $statement = $dbcon->prepare($sql);
            $statement->execute([':sqlfile' => $nextRun]);
            echo " - Successful\r\n";
        } else {
            echo " - Failed\r\n";
        }
    }
    echo "Done !\r\n";
}
