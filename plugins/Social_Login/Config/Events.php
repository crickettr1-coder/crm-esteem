<?php

namespace Social_Login\Config;

use CodeIgniter\Events\Events;

Events::on('pre_system', function () {
    helper("social_login_general");
});

