<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1> <?php echo app_lang('google_sheets'); ?></h1>
            <div class="title-button-group">
                <?php
                $can_manage_google_sheets_integration = can_manage_google_sheets_integration();
                if ($can_manage_google_sheets_integration) {
                    echo modal_anchor(get_uri("google_sheets/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('google_sheets_integration_add_spreadsheet'), array("class" => "btn btn-default", "title" => app_lang('google_sheets_integration_add_spreadsheet')));
                }
                ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="google-sheets-table" class="display" cellspacing="0" width="100%">            
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    "use strict";
    
    $(document).ready(function () {
        var actionVisibility = false;
<?php if ($can_manage_google_sheets_integration) { ?>
            actionVisibility = true;
<?php } ?>

        $("#google-sheets-table").appTable({
            source: '<?php echo_uri("google_sheets/list_data") ?>',
            order: [[0, 'desc']],
            columns: [
                {title: '<?php echo app_lang("title"); ?>', "class": "w300"},
                {title: '<?php echo app_lang("description"); ?>', "class": "w300"},
                {title: '<?php echo app_lang("created_by"); ?>', "class": "w200"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100", visible: actionVisibility}
            ]
        });
    });
</script>