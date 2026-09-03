<?php echo form_open(get_uri("mailbox_settings/save_outgoing_email_settings"), array("id" => "mailbox-outgoing-email-settings-form", "class" => "general-form bg-white", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="use_global_email" class=" col-md-3"><?php echo app_lang('mailbox_use_global_email_settings'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_checkbox(
                            "use_global_email", "1", $model_info->use_global_email ? true : false, "id='use_global_email' class='form-check-input'"
                    );
                    ?>
                </div>
            </div>
        </div>
        <div id="all_outgoing_email_settings" class="<?php echo $model_info->use_global_email ? "hide" : ""; ?>">

            <div class="form-group">
                <div class="row">
                    <label for="email_protocol" class=" col-md-3"><?php echo app_lang('email_protocol'); ?></label>
                    <div class=" col-md-9">
                        <?php
                        $email_protocols = array(
                            "mail" => "Mail",
                            "smtp" => "SMTP",
                            "microsoft_outlook" => "Microsoft Outlook",
                        );
                        echo form_dropdown(
                                "email_protocol", $email_protocols, $model_info->email_protocol, "class='select2 mini' id='email-protocol'"
                        );
                        ?>
                    </div>
                </div>
            </div>

            <div id="email-send-from-name" class="form-group <?php echo $model_info->email_protocol === "microsoft_outlook" ? "hide" : ""; ?>">
                <div class="form-group">
                    <div class="row">
                        <label for="email_sent_from_name" class=" col-md-3"><?php echo app_lang('email_sent_from_name'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "email_sent_from_name",
                                "name" => "email_sent_from_name",
                                "value" => $model_info->email_sent_from_name ? $model_info->email_sent_from_name : $model_info->title,
                                "class" => "form-control",
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>
            </div>     

            <div id="mail-settings-area" class="form-group <?php echo ($model_info->email_protocol === "mail" || !$model_info->email_protocol) ? "" : "hide"; ?>">
                <div class="form-group">
                    <div class="row">
                        <label for="email_sent_from_address" class=" col-md-3"><?php echo app_lang('email_sent_from_address'); ?></label>
                        <div class=" col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "email_sent_from_address",
                                "name" => "email_sent_from_address",
                                "value" => $model_info->email_sent_from_address ? $model_info->email_sent_from_address : $model_info->imap_email,
                                "class" => "form-control",
                                "placeholder" => "somemail@somedomain.com",
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="smtp-settings-area" class="<?php echo $model_info->email_protocol === "smtp" ? "" : "hide"; ?>">
                <div class="form-group">
                    <div class="row">
                        <label for="email_smtp_host" class=" col-md-3"><?php echo app_lang('email_smtp_host'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "email_smtp_host",
                                "name" => "email_smtp_host",
                                "value" => $model_info->email_smtp_host,
                                "class" => "form-control",
                                "placeholder" => app_lang('email_smtp_host'),
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label for="email_smtp_user" class=" col-md-3"><?php echo app_lang('email_smtp_user'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "email_smtp_user",
                                "name" => "email_smtp_user",
                                "value" => $model_info->email_smtp_user,
                                "class" => "form-control",
                                "placeholder" => app_lang('email_smtp_user'),
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label for="email_smtp_pass" class=" col-md-3"><?php echo app_lang('email_smtp_password'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_password(array(
                                "id" => "email_smtp_pass",
                                "name" => "email_smtp_pass",
                                "value" => $model_info->email_smtp_pass ? "******" : "",
                                "class" => "form-control",
                                "placeholder" => app_lang('email_smtp_password'),
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label for="email_smtp_port" class=" col-md-3"><?php echo app_lang('email_smtp_port'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "email_smtp_port",
                                "name" => "email_smtp_port",
                                "value" => $model_info->email_smtp_port,
                                "class" => "form-control",
                                "placeholder" => app_lang('email_smtp_port'),
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="email_smtp_security_type" class=" col-md-3"><?php echo app_lang('security_type'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_dropdown(
                                    "email_smtp_security_type", array(
                                "none" => "-",
                                "tls" => "TLS",
                                "ssl" => "SSL"
                                    ), $model_info->email_smtp_security_type, "class='select2 mini'"
                            );
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group"></div> <!-- to prevent border issue-->

            </div>

            <div id="microsoft-outlook-area" class="<?php echo $model_info->email_protocol === "microsoft_outlook" ? "" : "hide"; ?>">
                <div class="form-group">
                    <div class="row">
                        <label class=" col-md-12">
                            <?php echo app_lang("get_your_app_credentials_from_here") . " " . anchor("https://portal.azure.com/", "Microsoft Azure Portal", array("target" => "_blank")); ?>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="outlook_smtp_client_id" class=" col-md-3"><?php echo app_lang('google_client_id'); ?></label>
                        <div class=" col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "outlook_smtp_client_id",
                                "name" => "outlook_smtp_client_id",
                                "value" => $model_info->outlook_smtp_client_id,
                                "class" => "form-control",
                                "placeholder" => app_lang('google_client_id'),
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="outlook_smtp_client_secret" class=" col-md-3"><?php echo app_lang('google_client_secret'); ?></label>
                        <div class=" col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "outlook_smtp_client_secret",
                                "name" => "outlook_smtp_client_secret",
                                "value" => $model_info->outlook_smtp_client_secret,
                                "class" => "form-control",
                                "placeholder" => app_lang('google_client_secret'),
                                "data-rule-required" => true,
                                "data-msg-required" => app_lang("field_required"),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="redirect_uri" class=" col-md-3"><i data-feather="alert-triangle" class="icon-16 text-warning"></i> <?php echo app_lang('remember_to_add_this_url_in_authorized_redirect_uri'); ?></label>
                        <div class=" col-md-9">
                            <?php
                            echo "<pre class='mt5'>" . get_uri("mailbox_microsoft_api/save_outlook_smtp_access_token/$model_info->id") . "</pre>"
                            ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <label for="status" class=" col-md-3"><?php echo app_lang('status'); ?></label>
                        <div class=" col-md-9">
                            <?php if ($model_info->outlook_smtp_authorized) { ?>
                                <span class="ml5 badge bg-success"><?php echo app_lang("authorized"); ?></span>
                            <?php } else { ?>
                                <span class="ml5 badge" style="background:#F9A52D;"><?php echo app_lang("unauthorized"); ?></span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="form-group"></div> <!-- to prevent border issue-->
            </div>


            <div class="form-group">
                <div class="row">
                    <label for="send_test_mail_to" class=" col-md-3"><?php echo app_lang('send_test_mail_to'); ?>
                        <span class="help" data-container="body" data-bs-toggle="tooltip" title="Keep it blank if you are not interested to send test mail"><i data-feather="help-circle" class="icon-16"></i></span>

                    </label>
                    <div class="col-md-9">
                        <?php
                        echo form_input(array(
                            "id" => "send_test_mail_to",
                            "name" => "send_test_mail_to",
                            "value" => "",
                            "class" => "form-control",
                            "placeholder" => "youremail@address.com",
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button id="save-button" type="submit" class="btn btn-primary <?php echo $model_info->email_protocol === "microsoft_outlook" ? "hide" : "" ?>"><span data-feather='check-circle' class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    <button id="save-and-authorize-button" type="submit" class="btn btn-primary ml5 <?php echo $model_info->email_protocol === "microsoft_outlook" ? "" : "hide" ?>"><span data-feather='check-circle' class="icon-16"></span> <?php echo app_lang('save_and_authorize'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    "use strict";


    $(document).ready(function () {

        $("#mailbox-outgoing-email-settings-form").appForm({
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});

                //for microsoft outlook, redirect to authorization
                if ($("#email-protocol").val() === "microsoft_outlook") {
                    window.location.href = result.redirect_uri;
                }
            }
        });

        $("#mailbox-outgoing-email-settings-form .select2").select2();
        $('[data-bs-toggle="tooltip"]').tooltip();

        $("#use_global_email").click(function () {
            if ($(this).is(":checked")) {
                $("#all_outgoing_email_settings").addClass("hide");
            } else {
                $("#all_outgoing_email_settings").removeClass("hide");
            }
        });

        var $mailSettingsArea = $("#mail-settings-area"),
                $smtpSettingsArea = $("#smtp-settings-area"),
                $microsoftOutlookArea = $("#microsoft-outlook-area"),
                $saveAndAuthorizeBtn = $("#save-and-authorize-button"),
                $saveBtn = $("#save-button"),
                $emailSendFromName = $("#email-send-from-name");

        $("#email-protocol").select2().on("change", function () {
            var value = $(this).val();
            if (value === "mail") {
                $mailSettingsArea.removeClass("hide");
                $smtpSettingsArea.addClass("hide");
                $microsoftOutlookArea.addClass("hide");

                $saveBtn.removeClass("hide");
                $saveAndAuthorizeBtn.addClass("hide");
                $emailSendFromName.removeClass("hide");
            } else if (value === "smtp") {
                $smtpSettingsArea.removeClass("hide");
                $mailSettingsArea.addClass("hide");
                $microsoftOutlookArea.addClass("hide");

                $saveBtn.removeClass("hide");
                $saveAndAuthorizeBtn.addClass("hide");
                $emailSendFromName.removeClass("hide");
            } else {
                $microsoftOutlookArea.removeClass("hide");
                $mailSettingsArea.addClass("hide");
                $smtpSettingsArea.addClass("hide");

                $saveBtn.addClass("hide");
                $saveAndAuthorizeBtn.removeClass("hide");
                $emailSendFromName.addClass("hide");
            }
        });
    });
</script>