<?php
namespace Esteem_Email_Marketing\Controllers;
use App\Controllers\Security_Controller;

class Contacts extends Security_Controller
{
    private $db; private $p;
    public function __construct(){ parent::__construct(); $this->access_only_admin(); $this->db=db_connect('default'); $this->p=get_db_prefix(); }
    public function index()
    {
        $q=trim((string)$this->request->getGet('q'));
        $builder=$this->db->table($this->p.'users u')->select("CASE WHEN c.is_lead=1 THEN 'lead' ELSE 'client' END AS entity_type,u.id AS entity_id,u.email,COALESCE(mc.consent,'Unknown') AS consent,mc.updated_at,u.first_name,u.last_name,c.company_name,c.labels,c.state,c.city,c.zip")->join($this->p.'clients c','c.id=u.client_id AND c.deleted=0','inner')->join($this->p.'marketing_consent mc',"mc.entity_id=u.id AND mc.entity_type=CASE WHEN c.is_lead=1 THEN 'lead' ELSE 'client' END",'left')->where(['u.deleted'=>0,'u.is_primary_contact'=>1])->where('u.email !=','');
        if($q!=='')$builder->groupStart()->like('u.email',$q)->orLike('u.first_name',$q)->orLike('u.last_name',$q)->orLike('c.company_name',$q)->groupEnd();
        $contacts=$builder->orderBy('mc.updated_at','DESC')->limit(100)->get()->getResult();
        $suppressed=$this->db->table($this->p.'email_suppressions')->orderBy('created_at','DESC')->limit(100)->get()->getResult();
        return $this->template->rander('Esteem_Email_Marketing\\Views\\contacts\\index',['contacts'=>$contacts,'suppressed'=>$suppressed,'q'=>$q]);
    }
    public function export()
    {
        $rows=$this->db->query("SELECT u.email,u.first_name,u.last_name,CASE WHEN c.is_lead=1 THEN 'lead' ELSE 'client' END AS type,COALESCE(mc.consent,'Unknown') AS consent,c.state,c.city,c.zip FROM {$this->p}users u JOIN {$this->p}clients c ON c.id=u.client_id AND c.deleted=0 LEFT JOIN {$this->p}marketing_consent mc ON mc.entity_id=u.id AND mc.entity_type=CASE WHEN c.is_lead=1 THEN 'lead' ELSE 'client' END WHERE u.deleted=0 AND u.is_primary_contact=1 AND u.email<>'' ORDER BY u.id")->getResultArray();
        $out="Email,First Name,Last Name,Type,Consent,State,Suburb,Postcode\r\n";
        foreach($rows as $row){$values=[];foreach($row as $value)$values[]=str_replace('"','""',(string)$value);$out.='"'.implode('","',$values)."\"\r\n";}
        return $this->response->setHeader('Content-Type','text/csv; charset=UTF-8')->setHeader('Content-Disposition','attachment; filename="marketing-contacts.csv"')->setBody($out);
    }
}
