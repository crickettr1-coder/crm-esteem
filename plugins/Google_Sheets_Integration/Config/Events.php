<?php

namespace Google_Sheets_Integration\Config;

use CodeIgniter\Events\Events;

Events::on('pre_system', function () {
    helper("google_sheets_integration_general");
});