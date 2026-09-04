<?php
$db = db_connect('default'); $sql = file_get_contents(PLUGINPATH . 'Esteem_Email_Marketing/install/database.sql');
$sql = str_replace('`email_', '`' . get_db_prefix() . 'email_', $sql);
$sql = str_replace('`marketing_', '`' . get_db_prefix() . 'marketing_', $sql);
foreach (explode(';', $sql) as $query) { if (trim($query) && !$db->query($query)) { echo json_encode(['success'=>false,'message'=>'Email Marketing database installation failed.']); exit; } }
