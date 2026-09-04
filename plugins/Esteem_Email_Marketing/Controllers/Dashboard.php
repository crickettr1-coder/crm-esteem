<?php
namespace Esteem_Email_Marketing\Controllers;
use App\Controllers\Security_Controller;

class Dashboard extends Security_Controller
{
    private $db; private $p;
    public function __construct(){ parent::__construct(); $this->access_only_admin(); $this->db=db_connect('default'); $this->p=get_db_prefix(); }
    public function index()
    {
        $totals=[];
        $totals['opted_in']=(int)$this->db->table($this->p.'marketing_consent')->where('consent','Opted In')->countAllResults();
        $totals['suppressed']=(int)$this->db->table($this->p.'email_suppressions')->countAllResults();
        foreach(['draft','scheduled','completed'] as $status)$totals[$status]=(int)$this->db->table($this->p.'email_campaigns')->where('status',$status)->countAllResults();
        foreach(['sent','failed','skipped','unsubscribed'] as $status)$totals[$status]=(int)$this->db->table($this->p.'email_campaign_recipients')->where('status',$status)->countAllResults();
        $activity=$this->db->query("SELECT r.email,r.status,r.queued_at,r.sent_at,c.name AS campaign_name FROM {$this->p}email_campaign_recipients r JOIN {$this->p}email_campaigns c ON c.id=r.campaign_id ORDER BY r.id DESC LIMIT 10")->getResult();
        $failures=$this->db->query("SELECT r.email,r.error_details,r.sent_at,r.queued_at,c.name AS campaign_name FROM {$this->p}email_campaign_recipients r JOIN {$this->p}email_campaigns c ON c.id=r.campaign_id WHERE r.status='failed' ORDER BY r.id DESC LIMIT 10")->getResult();
        return $this->template->rander('Esteem_Email_Marketing\\Views\\dashboard\\index',['totals'=>$totals,'activity'=>$activity,'failures'=>$failures]);
    }
}
