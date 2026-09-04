<div class="page-title clearfix"><h1>Email Automation</h1><a href="<?php echo site_url('email_marketing'); ?>" class="btn btn-default float-end">Campaigns</a></div>
<div class="alert alert-warning">Rules are disabled by default. Test mode remains enforced by the existing queue.</div>
<div class="card"><div class="card-body"><form method="post" action="<?php echo site_url('email_marketing/automation/save'); ?>">
<input type="hidden" name="<?php echo csrf_token(); ?>" value="<?php echo csrf_hash(); ?>">
<div class="row"><div class="col-md-3"><label>Name</label><input required class="form-control" name="name"></div>
<div class="col-md-3"><label>Trigger</label><select class="form-control" name="trigger_type"><option value="new_lead">New opted-in lead</option><option value="quote_sent">Lead status changed to Quote Sent</option><option value="no_response">No response after days</option></select></div>
<div class="col-md-2"><label>Delay minutes</label><input type="number" min="0" class="form-control" name="delay_minutes" value="0"></div>
<div class="col-md-2"><label>No response days</label><input type="number" min="1" class="form-control" name="no_response_days" value="3"></div>
<div class="col-md-2"><label>Template ID</label><input required type="number" min="1" class="form-control" name="template_id"></div></div>
<div class="row mt15"><div class="col-md-6"><label>Quote Sent status ID</label><input type="number" min="1" class="form-control" name="quote_status_id"><small>Required for Quote Sent rules.</small></div>
<div class="col-md-6"><label>No-response lead status IDs</label><input class="form-control" name="status_ids" placeholder="Comma-separated IDs, e.g. 2,4,7"><small>Only these configured statuses are considered; comments are not inspected.</small></div></div>
<button class="btn btn-primary mt15">Save disabled rule</button></form></div></div>
<div class="card mt15"><table class="table"><thead><tr><th>Name</th><th>Trigger</th><th>Template</th><th>Statuses</th><th>Days</th><th>Status</th></tr></thead><tbody><?php foreach($rules as $r): ?><tr><td><?php echo esc($r->name); ?></td><td><?php echo esc($r->trigger_type); ?></td><td><?php echo (int)$r->template_id; ?></td><td><?php echo esc($r->trigger_type==='quote_sent'?$r->quote_status_id:$r->status_ids); ?></td><td><?php echo (int)$r->no_response_days; ?></td><td><?php echo $r->enabled?'Enabled':'Disabled'; ?></td></tr><?php endforeach; ?></tbody></table></div>
