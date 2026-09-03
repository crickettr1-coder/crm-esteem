<?php

namespace Easy_Backup\Config;

use CodeIgniter\Events\Events;

Events::on('pre_system', function () {
    helper("easy_backup_general");
});
