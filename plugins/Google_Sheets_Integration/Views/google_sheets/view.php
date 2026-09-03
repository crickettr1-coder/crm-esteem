<style type="text/css">
    #google-sheets-content {
        max-width: 700px;
        margin: auto;
    }

    .google-sheets-title {
        background: #f9fbfd;
        padding: 10px 10px 5px 60px;
    }
</style>

<div class="font-16 google-sheets-title">
    <i data-feather='file-plus' class='icon-16'></i> <?php echo $model_info->title; ?>
</div>

<div id="google-sheets-iframe-wrapper">
    <iframe width="100%" id="google-sheets-iframe" src="https://docs.google.com/spreadsheets/d/<?php echo $model_info->google_spreadsheet_id; ?>/edit?usp=sharing&widget=true&rm=embedded"></iframe>
</div>

<script type="text/javascript">
    "use strict";

    $(document).ready(function() {
        window.GoogleSheetsRefreshPageAfterUpdate = true;

        //set iframe height
        $("#google-sheets-iframe").height($(window).height() - 110);
    });
</script>