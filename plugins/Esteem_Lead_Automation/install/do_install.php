<?php

$db = db_connect('default');
$sql_file = PLUGINPATH . "Esteem_Lead_Automation/install/database.sql";

if (!is_file($sql_file)) {
    echo json_encode(array("success" => false, "message" => "The plugin database file could not be found."));
    exit();
}

$sql = file_get_contents($sql_file);
$dbprefix = get_db_prefix();
$sql = str_replace('CREATE TABLE IF NOT EXISTS `', 'CREATE TABLE IF NOT EXISTS `' . $dbprefix, $sql);
$sql = str_replace('INSERT INTO `', 'INSERT INTO `' . $dbprefix, $sql);

foreach (explode(';#', $sql) as $query) {
    $query = trim($query);
    if ($query && !$db->query($query)) {
        echo json_encode(array("success" => false, "message" => "Lead automation database installation failed."));
        exit();
    }
}

