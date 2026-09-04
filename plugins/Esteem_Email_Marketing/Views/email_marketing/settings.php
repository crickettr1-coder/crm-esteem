<div class="page-title clearfix"><h1>Email Marketing Settings</h1></div>
<?php if (!$mail_configured): ?><div class="alert alert-danger">CRM email transport is not configured. Queue and delivery are blocked.</div><?php endif; ?>
<div class="card"><div class="card-body"><form method="post" action="<?php echo site_url('email_marketing/save_settings'); ?>"><input type="hidden" name="<?php echo csrf_token(); ?>" value="<?php echo csrf_hash(); ?>">
<label class="d-block"><input type="checkbox" name="enabled" value="1" <?php echo (($settings['enabled']??'0')==='1')?'checked':''; ?>> Enable module</label>
<label class="d-block"><input type="checkbox" name="test_mode" value="1" <?php echo (($settings['test_mode']??'1')==='1')?'checked':''; ?>> Test mode (keep enabled during testing)</label>
<div class="form-group"><label>Approved test email addresses</label><textarea class="form-control" name="approved_test_emails" rows="3" placeholder="one email per line"><?php echo esc(implode("\n",$approved_test_emails??[])); ?></textarea><small>Only these addresses can receive test sends.</small></div>
<div class="form-group"><label>Default sender name</label><input class="form-control" name="default_sender_name" value="<?php echo esc($settings['default_sender_name']??'Esteem Energy'); ?>"></div>
<div class="form-group"><label>Default reply-to email</label><input type="email" class="form-control" name="default_reply_to" value="<?php echo esc($settings['default_reply_to']??''); ?>"></div>
<div class="form-group"><label>Batch size</label><input type="number" min="1" max="500" class="form-control" name="batch_size" value="<?php echo esc($settings['batch_size']??'25'); ?>"></div>
<div class="form-group"><label>Delay between messages (milliseconds)</label><input type="number" min="0" class="form-control" name="delay_between_messages" value="<?php echo esc($settings['delay_between_messages']??'0'); ?>"></div>
<div class="form-group"><label>Company/contact footer</label><textarea class="form-control" name="company_footer"><?php echo esc($settings['company_footer']??'Esteem Energy'); ?></textarea></div>
<button class="btn btn-primary">Save Settings</button></form></div></div>
