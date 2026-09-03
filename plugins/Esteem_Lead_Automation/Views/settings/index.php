<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "esteem_lead_automation";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>
        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="card-header"><h4><?php echo app_lang("esteem_lead_automation"); ?></h4></div>
                <?php echo form_open(get_uri("esteem_lead_automation_settings/save"), array("id" => "esteem-lead-automation-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>
                <div class="card-body">
                    <div class="form-group clearfix"><div class="row">
                        <label class="col-md-3" for="lead_automation_enabled"><?php echo app_lang("lead_automation_enabled"); ?></label>
                        <div class="col-md-9"><?php echo form_checkbox("lead_automation_enabled", "1", get_lead_automation_setting("lead_automation_enabled") === "1", "id='lead_automation_enabled' class='form-check-input'"); ?></div>
                    </div></div>
                    <div class="form-group clearfix"><div class="row">
                        <label class="col-md-3" for="lead_automation_delay_minutes"><?php echo app_lang("lead_automation_delay_minutes"); ?></label>
                        <div class="col-md-9"><input type="number" min="1" max="43200" class="form-control" id="lead_automation_delay_minutes" name="lead_automation_delay_minutes" value="<?php echo esc(get_lead_automation_setting("lead_automation_delay_minutes")); ?>" required></div>
                    </div></div>
                    <div class="form-group clearfix"><div class="row">
                        <label class="col-md-3" for="lead_automation_task_title"><?php echo app_lang("lead_automation_task_title"); ?></label>
                        <div class="col-md-9"><input type="text" maxlength="255" class="form-control" id="lead_automation_task_title" name="lead_automation_task_title" value="<?php echo esc(get_lead_automation_setting("lead_automation_task_title")); ?>" required><small class="text-muted">Use {Lead Name} to insert the lead name.</small></div>
                    </div></div>
                    <div class="alert alert-info">This automation creates tasks only. It does not email, call, message, or contact customers.</div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary"><?php echo app_lang("save"); ?></button></div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $("#esteem-lead-automation-settings-form").appForm({isModal: false});
});
</script>

