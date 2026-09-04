<?php
namespace Esteem_Email_Marketing\Libraries;
class Recipient_Service {
    private $db; private $p;
    public function __construct(){ $this->db=db_connect('default'); $this->p=get_db_prefix(); }
    public function query(array $f=[], $limit=50, $offset=0){
        $where=["u.deleted=0","u.email<>''","mc.consent='Opted In'","s.id IS NULL","u.user_type IN ('lead','client')"];$bind=[];
        if(($f['type']??'')==='lead')$where[]="u.user_type='lead'"; elseif(($f['type']??'')==='client')$where[]="u.user_type='client'";
        if(!empty($f['owner_id'])){$where[]='c.owner_id=?';$bind[]=(int)$f['owner_id'];} if(!empty($f['status_id'])){$where[]='c.lead_status_id=?';$bind[]=(int)$f['status_id'];} if(!empty($f['source_id'])){$where[]='c.lead_source_id=?';$bind[]=(int)$f['source_id'];} if(!empty($f['state'])){$where[]='c.state LIKE ?';$bind[]='%'.trim($f['state']).'%';} if(!empty($f['suburb'])){$where[]='c.city LIKE ?';$bind[]='%'.trim($f['suburb']).'%';} if(!empty($f['postcode'])){$where[]='c.zip=?';$bind[]=trim($f['postcode']);} if(!empty($f['created_from'])){$where[]='u.created_at>=?';$bind[]=$f['created_from'].' 00:00:00';} if(!empty($f['created_to'])){$where[]='u.created_at<=?';$bind[]=$f['created_to'].' 23:59:59';}
        $sql="SELECT u.id,u.user_type,u.email,u.first_name,u.last_name,u.phone,c.company_name,c.state,c.city,c.zip,c.owner_id FROM {$this->p}users u LEFT JOIN {$this->p}clients c ON c.id=u.client_id LEFT JOIN {$this->p}marketing_consent mc ON mc.entity_id=u.id AND mc.entity_type=u.user_type LEFT JOIN {$this->p}email_suppressions s ON s.email=LOWER(u.email) WHERE ".implode(' AND ',$where)." ORDER BY u.id DESC LIMIT ".(int)$limit." OFFSET ".(int)$offset;
        return $this->db->query($sql,$bind)->getResult();
    }
    public function count(array $f=[]){ return count($this->query($f,100000,0)); }
}
