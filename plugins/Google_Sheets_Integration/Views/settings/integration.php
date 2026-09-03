<div class="card no-border clearfix mb0">
    <?php echo form_open(get_uri("google_sheets_integration_settings/save"), array("id" => "google_sheets_integration-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
    <div class="card-body">
        <div class="form-group">
            <div class="row">
                <label for="integrate_google_sheets" class=" col-md-2"><?php echo app_lang('google_sheets_integration_integrate_google_sheets'); ?></label>

                <div class="col-md-10">
                    <?php
                    echo form_checkbox("integrate_google_sheets", "1", get_google_sheets_integration_setting("integrate_google_sheets") ? true : false, "id='integrate_google_sheets' class='form-check-input'");
                    ?> 
                </div>
            </div>
        </div>

        <div class="clearfix integrate-with-google-sheets-details-section <?php echo get_google_sheets_integration_setting("integrate_google_sheets") ? "" : "hide" ?>">
            <div class="form-group">
                <div class="row">
                    <label class=" col-md-12">
                    <?php echo app_lang("get_your_app_credentials_from_here") . " " . anchor("https://console.developers.google.com", "Google API Console", array("target" => "_blank")); ?>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="google_sheets_client_id" class=" col-md-2"><?php echo app_lang('google_drive_client_id'); ?></label>
                    <div class=" col-md-10">
                        <?php
                        echo form_input(array(
                            "id" => "google_sheets_client_id",
                            "name" => "google_sheets_client_id",
                            "value" => get_google_sheets_integration_setting('google_sheets_client_id'),
                            "class" => "form-control",
                            "placeholder" => app_lang('google_drive_client_id'),
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="google_sheets_client_secret" class=" col-md-2"><?php echo app_lang('google_drive_client_secret'); ?></label>
                    <div class=" col-md-10">
                        <?php
                        echo form_input(array(
                            "id" => "google_sheets_client_secret",
                            "name" => "google_sheets_client_secret",
                            "value" => get_google_sheets_integration_setting('google_sheets_client_secret'),
                            "class" => "form-control",
                            "placeholder" => app_lang('google_drive_client_secret'),
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="redirect_uri" class=" col-md-2"><i data-feather="alert-triangle" class="icon-16"></i> <?php echo app_lang('remember_to_add_this_url_in_authorized_redirect_uri'); ?></label>
                    <div class=" col-md-10">
                        <?php
                        echo "<pre class='mt5'>" . get_uri("google_sheets_integration_settings/save_access_token") . "</pre>"
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="status" class=" col-md-2"><?php echo app_lang('status'); ?></label>
                    <div class=" col-md-10">
                        <?php if (get_google_sheets_integration_setting('google_sheets_authorized')) { ?>
                            <span class="ml5 badge bg-success"><?php echo app_lang("authorized"); ?></span>
                        <?php } else { ?>
                            <span class="ml5 badge" style="background:#F9A52D;"><?php echo app_lang("unauthorized"); ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="card-footer">
        <button id="save-button" type="submit" class="btn btn-primary <?php echo get_google_sheets_integration_setting("integrate_google_sheets") ? "hide" : "" ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
        <button id="save-and-authorize-button" type="submit" class="btn btn-primary ml5 <?php echo get_google_sheets_integration_setting("integrate_google_sheets") ? "" : "hide" ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save_and_authorize'); ?></button>
    </div>
    <?php echo form_close(); ?>
</div>

<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        var $saveAndAuthorizeBtn = $("#save-and-authorize-button"),
                $saveBtn = $("#save-button"),
                $meetDetailsArea = $(".integrate-with-google-sheets-details-section");

        $("#google_sheets_integration-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});

                //if google sheets is enabled, redirect to authorization system
                if ($saveBtn.hasClass("hide")) {
                    window.location.href = "<?php echo_uri('google_sheets_integration_settings/authorize_google_sheets'); ?>";
                }
            }
        });

        //show/hide google sheets details area
        $(document).on("click", "#integrate_google_sheets", function () {
            if ($(this).is(":checked")) {
                $saveAndAuthorizeBtn.removeClass("hide");
                $saveBtn.addClass("hide");
                $meetDetailsArea.removeClass("hide");
            } else {
                $saveAndAuthorizeBtn.addClass("hide");
                $saveBtn.removeClass("hide");
                $meetDetailsArea.addClass("hide");
            }
        });
    });
</script>