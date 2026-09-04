<?php
namespace Esteem_Email_Marketing\Controllers;
use App\Controllers\Security_Controller;

class Stats extends Security_Controller
{
    public function __construct(){parent::__construct();$this->access_only_admin();}
    public function index(){ $db=db_connect('default');$p=get_db_prefix();$totals=[];foreach(['queued','sent','failed','skipped','unsubscribed'] as $s)$totals[$s]=(int)$db->table($p.'email_campaign_recipients')->where('status',$s)->countAllResults();$campaigns=$db->table($p.'email_campaigns')->orderBy('id','DESC')->limit(25)->get()->getResult();$breakdowns=[];foreach($campaigns as $c){$breakdowns[$c->id]=[];foreach(['queued','sent','failed','skipped','unsubscribed'] as $s)$breakdowns[$c->id][$s]=(int)$db->table($p.'email_campaign_recipients')->where(['campaign_id'=>$c->id,'status'=>$s])->countAllResults();}return $this->template->rander('Esteem_Email_Marketing\\Views\\stats\\index',['totals'=>$totals,'campaigns'=>$campaigns,'breakdowns'=>$breakdowns]); }
    public function export(){ $db=db_connect('default');$p=get_db_prefix();$rows=$db->query("SELECT c.name AS campaign,r.email,r.status,r.queued_at,r.sent_at,r.error_details FROM {$p}email_campaign_recipients r JOIN {$p}email_campaigns c ON c.id=r.campaign_id ORDER BY r.id DESC")->getResultArray();$out="Campaign,Email,Status,Queued At,Sent At,Error\r\n";foreach($rows as $r){$values=[];foreach($r as $v)$values[]=str_replace('"','""',(string)$v);$out.='"'.implode('","',$values)."\"\r\n";}return $this->response->setHeader('Content-Type','text/csv; charset=UTF-8')->setHeader('Content-Disposition','attachment; filename="email-delivery-log.csv"')->setBody($out); }
}
