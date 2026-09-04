<?php
namespace Esteem_Email_Marketing\Controllers;
use App\Controllers\Security_Controller;
class Consent extends Security_Controller {
 private $db;private $p;
 public function __construct(){parent::__construct();$this->access_only_admin();$this->db=db_connect('default');$this->p=get_db_prefix();}
 public function edit($type,$id){$type=$type==='lead'?'lead':'client';$row=$this->db->table($this->p.'marketing_consent')->where(['entity_type'=>$type,'entity_id'=>(int)$id])->get()->getRow();return $this->template->view('Esteem_Email_Marketing\\Views\\consent\\form',['type'=>$type,'id'=>(int)$id,'consent'=>$row->consent??'Unknown']);}
 public function save(){ $type=$this->request->getPost('entity_type')==='lead'?'lead':'client';$id=(int)$this->request->getPost('entity_id');$value=in_array($this->request->getPost('consent'),['Unknown','Opted In','Opted Out'],true)?$this->request->getPost('consent'):'Unknown';$email=$this->db->table($this->p.'users')->where('id',$id)->get()->getRow('email');if(!$email)return redirect()->back()->with('error','Contact not found.');$this->db->table($this->p.'marketing_consent')->replace(['entity_type'=>$type,'entity_id'=>$id,'email'=>strtolower($email),'consent'=>$value,'updated_at'=>date('Y-m-d H:i:s')]);if($value==='Opted Out')$this->db->table($this->p.'email_suppressions')->ignore(true)->insert(['email'=>strtolower($email),'entity_type'=>$type,'entity_id'=>$id,'reason'=>'marketing_opt_out','created_at'=>date('Y-m-d H:i:s')]);elseif($value==='Opted In')$this->db->table($this->p.'email_suppressions')->where(['email'=>strtolower($email),'entity_type'=>$type,'entity_id'=>$id,'reason'=>'marketing_opt_out'])->delete();return redirect()->back()->with('success','Marketing consent updated.');}
}
