<?php
namespace Esteem_Email_Marketing\Controllers;
use App\Controllers\Security_Controller;
class Templates extends Security_Controller {
 private $db;private $p;private $vars=['FIRST_NAME','LAST_NAME','FULL_NAME','EMAIL','PHONE','STATE','POSTCODE','LEAD_OWNER','UNSUBSCRIBE_URL','COMPANY_NAME'];
 public function __construct(){parent::__construct();$this->access_only_admin();$this->db=db_connect('default');$this->p=get_db_prefix();}
 public function index(){return $this->template->rander('Esteem_Email_Marketing\\Views\\templates\\index',['templates'=>$this->db->table($this->p.'email_marketing_templates')->orderBy('id','DESC')->get()->getResult()]);}
 public function create(){return $this->template->rander('Esteem_Email_Marketing\\Views\\templates\\form',['template'=>null,'vars'=>$this->vars]);}
 public function edit($id){return $this->template->rander('Esteem_Email_Marketing\\Views\\templates\\form',['template'=>$this->db->table($this->p.'email_marketing_templates')->where('id',(int)$id)->get()->getRow(),'vars'=>$this->vars]);}
 public function save(){ $d=$this->request->getPost();$body=(string)($d['html_content']??'');preg_match_all('/\{([A-Z_]+)\}/',$body.' '.($d['subject']??''),$m);$bad=array_diff(array_unique($m[1]),$this->vars);if($bad)return redirect()->back()->with('error','Unknown personalisation variable: '.implode(', ',$bad));$this->db->table($this->p.'email_marketing_templates')->insert(['name'=>trim((string)($d['name']??'')),'subject'=>trim((string)($d['subject']??'')),'html_content'=>$body,'created_by'=>(int)$this->login_user->id,'created_at'=>date('Y-m-d H:i:s')]);return redirect()->to(site_url('email_marketing/templates'))->with('success','Template saved.');}
 public function duplicate($id){$t=$this->db->table($this->p.'email_marketing_templates')->where('id',(int)$id)->get()->getRow();if($t){$this->db->table($this->p.'email_marketing_templates')->insert(['name'=>$t->name.' copy','subject'=>$t->subject,'html_content'=>$t->html_content,'created_by'=>(int)$this->login_user->id,'created_at'=>date('Y-m-d H:i:s')]);}return redirect()->to(site_url('email_marketing/templates'));}
 public function delete($id){$this->db->table($this->p.'email_marketing_templates')->where('id',(int)$id)->delete();return redirect()->to(site_url('email_marketing/templates'));}
 public function preview($id){$t=$this->db->table($this->p.'email_marketing_templates')->where('id',(int)$id)->get()->getRow();return $this->template->view('Esteem_Email_Marketing\\Views\\templates\\preview',['template'=>$t]);}
}
