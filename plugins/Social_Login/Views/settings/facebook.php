<div class="card no-border clearfix mb0">

    <?php if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') { //facebook login will only work on https ?>

        <?php echo form_open(get_uri("social_login_settings/save_facebook_settings"), array("id" => "social_login-facebook-form", "class" => "general-form dashed-row", "role" => "form")); ?>

        <div class="card-body">

            <div class="form-group">
                <div class="row">
                    <label for="enable_facebook_login" class="col-md-2 col-xs-8 col-sm-4"><?php echo app_lang('social_login_enable_facebook_login'); ?></label>
                    <div class="col-md-10 col-xs-4 col-sm-8">
                        <?php
                        echo form_checkbox("enable_facebook_login", "1", get_social_login_setting("enable_facebook_login") ? true : false, "id='enable_facebook_login' class='form-check-input ml15'");
                        ?>
                    </div>
                </div>
            </div>

            <div class="facebook-login-show-hide-area <?php echo get_social_login_setting("enable_facebook_login") ? "" : "hide" ?>">
                <div class="form-group">
                    <div class="row">
                        <label class=" col-md-12">
                            <?php echo app_lang("get_your_app_credentials_from_here") . " " . anchor("https://developers.facebook.com/apps", "Facebook for Developers", array("target" => "_blank")); ?>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="facebook_login_app_id" class=" col-md-2">App ID</label>
                        <div class=" col-md-10">
                            <?php
                            echo form_input(array(
                                "id" => "facebook_login_app_id",
                                "name" => "facebook_login_app_id",
                                "value" => get_social_login_setting('facebook_login_app_id'),
                                "class" => "form-control",
                                "placeholder" => "App ID",
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="facebook_login_app_secret" class=" col-md-2">App secret</label>
                        <div class=" col-md-10">
                            <?php
                            echo form_input(array(
                                "id" => "facebook_login_app_secret",
                                "name" => "facebook_login_app_secret",
                                "value" => get_social_login_setting('facebook_login_app_secret'),
                                "class" => "form-control",
                                "placeholder" => "App secret",
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="redirect_uri" class=" col-md-2"><i data-feather="alert-triangle" class="icon-16"></i> <?php echo app_lang('social_login_remember_to_add_this_url_in_valid_oauth_redirect_uris'); ?></label>
                        <div class=" col-md-10">
                            <?php
                            echo "<pre class='mt5'>" . get_uri("social_login/authenticate_facebook_login") . "</pre>"
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="status" class=" col-md-2"><?php echo app_lang('status'); ?></label>
                        <div class=" col-md-10">
                            <?php if (get_social_login_setting("facebook_login_authorized")) { ?>
                                <span class="ml5 badge bg-success"><?php echo app_lang("authorized"); ?></span>
                            <?php } else { ?>
                                <span class="ml5 badge social_login-badge-alert"><?php echo app_lang("unauthorized"); ?></span>
                            <?php } ?>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <div class="card-footer">
            <button id="save-button-facebook" type="submit" class="btn btn-primary <?php echo get_social_login_setting("enable_facebook_login") ? "hide" : "" ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
            <button id="save-and-authorize-button-facebook" type="submit" class="btn btn-primary ml5 <?php echo get_social_login_setting("enable_facebook_login") ? "" : "hide" ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save_and_authorize'); ?></button>
        </div>
        <?php echo form_close(); ?>

    <?php } else { ?>

        <div class="card-body">
            <i data-feather='alert-triangle' class="icon-16 text-danger"></i> <?php echo app_lang("social_login_facebook_https_error_help_message"); ?>
        </div>

    <?php } ?>
</div>


<script type="text/javascript">
    "use strict";
    
    $(document).ready(function () {
        var $saveAndAuthorizeBtn = $("#save-and-authorize-button-facebook"),
                $saveBtn = $("#save-button-facebook"),
                $loginDetailsArea = $(".facebook-login-show-hide-area");

        $("#social_login-facebook-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});

                //if facebook login is enabled, redirect to authorization system
                if ($saveBtn.hasClass("hide")) {
                    window.location.href = "<?php echo_uri('social_login/authorize_facebook_login'); ?>";
                }
            }
        });

        //show/hide facebook login details area
        $("#enable_facebook_login").on("click", function () {
            if ($(this).is(":checked")) {
                $saveAndAuthorizeBtn.removeClass("hide");
                $loginDetailsArea.removeClass("hide");
                $saveBtn.addClass("hide");
            } else {
                $saveAndAuthorizeBtn.addClass("hide");
                $loginDetailsArea.addClass("hide");
                $saveBtn.removeClass("hide");
            }
        });

    });
</script>