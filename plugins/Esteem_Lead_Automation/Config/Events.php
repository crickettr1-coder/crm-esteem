<?php

namespace Esteem_Lead_Automation\Config;

use CodeIgniter\Events\Events;

Events::on('pre_system', function () {
    helper("esteem_lead_automation_helper");
});

