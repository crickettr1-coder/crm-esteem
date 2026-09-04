<?php
namespace Esteem_Email_Marketing\Controllers;
use App\Controllers\Security_Controller;
class Automation_Rules extends Security_Controller {
 public function __construct(){parent::__construct();$this->access_only_admin();}
 public function save(){ $d=$this->request->getPost();$type=in_array($d['trigger_type']??'', ['new_lead','quote_sent','no_response'],true)?$d['trigger_type']:'new_lead';$db=db_connect('default');$db->table(get_db_prefix().'email_automation_rules')->insert(['name'=>trim((string)($d['name']??'')),'trigger_type'=>$type,'delay_minutes'=>max(0,(int)($d['delay_minutes']??0)),'no_response_days'=>max(1,(int)($d['no_response_days']??3)),'status_ids'=>preg_replace('/[^0-9,]/','',(string)($d['status_ids']??'')),'quote_status_id'=>max(0,(int)($d['quote_status_id']??0)),'template_id'=>max(0,(int)($d['template_id']??0)),'enabled'=>0,'created_by'=>(int)$this->login_user->id,'created_at'=>date('Y-m-d H:i:s')]);return redirect()->to(site_url('email_marketing/automation'))->with('success','Automation rule saved disabled.'); }
}
