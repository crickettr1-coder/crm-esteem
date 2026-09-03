<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "easy_backup";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>
        <div class="col-sm-9 col-lg-10">

            <div class="card">
                <div class="card-header">
                    <h4><?php echo app_lang("easy_backup"); ?></h4>
                </div>

                <?php echo form_open(get_uri("easy_backup_settings/save"), array("id" => "easy-backup-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>

                <div class="card-body general-form dashed-row">
                    <div class="form-group clearfix">
                        <div class="row">
                            <label for="cron_job_link" class=" col-md-2"><?php echo app_lang('cron_job_link'); ?></label>
                            <div class=" col-md-10"><?php
                                                    echo get_uri("easy_backup");
                                                    ?></div>
                        </div>
                    </div>
                    <div class="form-group clearfix">
                        <div class="row">
                            <label for="last_cron_job_run" class=" col-md-2"><?php echo app_lang('last_cron_job_run'); ?></label>
                            <div class=" col-md-10">
                                <?php
                                $status_class = "bg-dark";
                                $last_cron_job_time = get_easy_backup_setting('last_cron_job_time');
                                if ($last_cron_job_time) {
                                    $text = format_to_datetime(date('Y-m-d H:i:s', $last_cron_job_time));

                                    //show success color if last execution time is less then 60 min
                                    if (round(abs($last_cron_job_time - strtotime(get_current_utc_time())) / 60) <= 60) {
                                        $status_class = "bg-success";
                                    }
                                } else {
                                    $text = app_lang('never');
                                    $status_class = "bg-danger";
                                }

                                echo "<span class='badge $status_class'>" . $text . "</span>";
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group clearfix">
                        <div class="row">
                            <label class=" col-md-2">cPanel Cron Job Command *</label>
                            <div class=" col-md-10">
                                <div>
                                    <?php echo "<pre>wget -q -O- " . get_uri("easy_backup") . "</pre>"; ?>
                                </div>

                                <div class="">
                                    <?php echo anchor(get_uri("easy_backup"), app_lang("trigger_manually"), array("target" => "_blank", "class" => "btn btn-default mt15")); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label class=" col-md-2"><?php echo app_lang('easy_backup_backup_options'); ?></label>
                            <div class=" col-md-10">

                                <div>
                                    <?php echo form_checkbox("easy_backup_all_files", "1", get_easy_backup_setting("easy_backup_all_files") ? true : false, "id='easy_backup_all_files' class='form-check-input mr15 inline-block'"); ?>
                                    <label for="easy_backup_all_files"><?php echo app_lang('easy_backup_all_files'); ?></label>
                                </div>

                                <div>
                                    <?php echo form_checkbox("easy_backup_database", "1", get_easy_backup_setting("easy_backup_database") ? true : false, "id='easy_backup_database' class='form-check-input mr15 inline-block'"); ?>
                                    <label for="easy_backup_database"><?php echo app_lang('easy_backup_database'); ?></label>
                                </div>

                            </div>
                        </div>
                    </div>


                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-12">
                                <i data-feather='info' class="icon-16"></i> <?php echo app_lang("easy_backup_help_message"); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary mr15"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>

                    <?php echo js_anchor("<i data-feather='download-cloud' class='icon-16'></i> <span id='easy-backup-download-btn-text'>" . app_lang('easy_backup_download_instant_backup') . "</span>", array("id" => "easy-backup-download-btn", "class" => "btn btn-primary spinning-btn mr5 float-end", "data-inline-loader" => "1")); ?>
                    <?php echo ajax_anchor(get_uri("easy_backup_settings/backup"), "<i data-feather='upload-cloud' class='icon-16'></i> " . app_lang('easy_backup_backup_now'), array("class" => "btn btn-success spinning-btn mr5 float-end", "data-inline-loader" => "1", "data-show-response" => true)); ?>
                </div>

                <?php echo form_close(); ?>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    "use strict";

    $(document).ready(function() {
        $("#easy-backup-settings-form").appForm({
            isModal: false,
            onSuccess: function(result) {
                appAlert.success(result.message, {
                    duration: 10000
                });
            }
        });

        var $easyBackupDownloadBtn = $("#easy-backup-download-btn"),
            $easyBackupDownloadBtnText = $("#easy-backup-download-btn-text");

        $easyBackupDownloadBtn.click(function() {

            $easyBackupDownloadBtn.addClass("spinning");
            $easyBackupDownloadBtnText.html("<?php echo app_lang("easy_backup_preparing_backup"); ?>...");

            $.ajax({
                url: "<?php echo get_uri("easy_backup_settings/download"); ?>",
                type: 'POST',
                dataType: 'json',
                success: function(result) {
                    if (result.success) {

                        $easyBackupDownloadBtnText.html("<?php echo app_lang("easy_backup_downloading_backup"); ?>...");

                        var zipFileUrl = result.zip_file_url;

                        $.ajax({
                            url: '<?php echo base_url(); ?>/' + zipFileUrl,
                            method: 'GET',
                            xhr: function() {
                                //show loader
                                var xhr = new XMLHttpRequest();
                                xhr.onprogress = function(e) {
                                    if (e.lengthComputable) {
                                        var percent = (e.loaded / e.total) * 100;
                                        $('<style>#easy-backup-download-btn:after{width: ' + percent + '% !important}</style>').appendTo('head');
                                    }
                                };
                                return xhr;
                            },
                            xhrFields: {
                                responseType: 'arraybuffer' // Treat the response as binary data
                            },
                            success: function(data) {
                                // Create a Blob object from the binary data
                                var blob = new Blob([data], {
                                    type: 'application/zip'
                                }); // You should set the correct MIME type for your file

                                // Create a temporary URL to the Blob
                                var url = window.URL.createObjectURL(blob);

                                // Create a hidden <a> element to trigger the download
                                var a = document.createElement('a');
                                a.style.display = 'none';
                                a.href = url;
                                a.download = '<?php echo get_setting('app_title'); ?>.zip'; // Set the desired file name

                                // Trigger a click event on the <a> element to start the download
                                document.body.appendChild(a);
                                a.click();

                                // Clean up after the download is initiated
                                window.URL.revokeObjectURL(url);

                                // Make an Ajax POST request to the server to delete the file
                                $.ajax({
                                    url: '<?php echo get_uri("easy_backup_settings/delete_temp_backup"); ?>',
                                    method: 'POST',
                                    data: {
                                        zip_file_url: zipFileUrl
                                    }
                                });

                                $easyBackupDownloadBtn.removeClass("spinning");
                                $easyBackupDownloadBtnText.html("<?php echo app_lang("easy_backup_download_instant_backup"); ?>");
                                $('<style>#easy-backup-download-btn:after{width: 0% !important}</style>').appendTo('head');
                            },
                            error: function(xhr, status, error) {
                                console.log('File download failed:', error);
                            }
                        });

                    } else {
                        appAlert.error(result.message);
                    }
                }
            });
        });
    });
</script>

<style type="text/css">
    #easy-backup-download-btn:after {
        content: "";
        width: 0%;
        height: 100%;
        position: absolute;
        background: #ffffff2e;
        left: 0;
        top: 0;
        border-radius: 5px;
        transition: 1s;
    }
</style>