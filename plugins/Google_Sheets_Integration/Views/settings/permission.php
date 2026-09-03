<li>
    <span data-feather="key" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang("google_sheets_integration_can_manage_google_sheets"); ?></h5>
    <div>
        <?php
        $google_sheets = get_array_value($permissions, "google_sheets");
        if (is_null($google_sheets)) {
            $google_sheets = "";
        }

        echo form_radio(array(
            "id" => "google_sheets_no",
            "name" => "google_sheets_permission",
            "value" => "",
            "class" => "form-check-input"
                ), $google_sheets, ($google_sheets === "") ? true : false);
        ?>
        <label for="google_sheets_no"><?php echo app_lang("no"); ?> </label>
    </div>
    <div>
        <?php
        echo form_radio(array(
            "id" => "google_sheets_yes",
            "name" => "google_sheets_permission",
            "value" => "all",
            "class" => "form-check-input"
                ), $google_sheets, ($google_sheets === "all") ? true : false);
        ?>
        <label for="google_sheets_yes"><?php echo app_lang("yes"); ?></label>
    </div>
</li>