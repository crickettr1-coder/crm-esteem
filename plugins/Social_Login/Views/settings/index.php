<style>
    /* this style will be needed only on this page, so don't create a new css file' */
    .social_login-badge-alert{
        background:#F9A52D;
    }
</style>

<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "social_login";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>
        <div class="col-sm-9 col-lg-10">

            <div class="card no-border clearfix ">

                <ul id="integration-tab" data-bs-toggle="ajax-tab" class="nav nav-tabs bg-white title" role="tablist">
                    <li class="title-tab"><h4 class="pl15 pt10 pr15"><?php echo app_lang("social_login"); ?></h4></li>
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("social_login_settings/google/"); ?>" data-bs-target="#social_login-google">Google</a></li>
                    <li><a role="presentation" data-bs-toggle="tab" href="<?php echo_uri("social_login_settings/facebook/"); ?>" data-bs-target="#social_login-facebook">Facebook</a></li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane fade" id="social_login-google"></div>
                    <div role="tabpanel" class="tab-pane fade" id="social_login-facebook"></div>
                </div>
            </div>
        </div>
    </div>
</div>
