<div class="form-group">
    <div class="row">
        <label for="client_can_access_google_sheets" class="col-md-2 col-xs-8 col-sm-4"><?php echo app_lang('google_sheets_integration_client_can_access_google_sheets'); ?></label>
        <div class="col-md-10 col-xs-4 col-sm-8">
            <?php
            echo form_checkbox("client_can_access_google_sheets", "1", get_google_sheets_integration_setting("client_can_access_google_sheets") ? true : false, "id='client_can_access_google_sheets' class='form-check-input ml15'");
            ?>
        </div>
    </div>
</div>