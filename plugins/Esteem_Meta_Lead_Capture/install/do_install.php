<?php
$db = db_connect('default');
$prefix = $db->getPrefix();
$sql = file_get_contents(__DIR__ . '/database.sql');
foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
    $statement = trim($statement);
    if ($statement) {
        $db->query(str_replace('`meta_lead_capture_', '`' . $prefix . 'meta_lead_capture_', $statement));
    }
}
