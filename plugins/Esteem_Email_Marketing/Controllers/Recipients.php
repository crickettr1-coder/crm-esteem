<?php
namespace Esteem_Email_Marketing\Controllers;
use App\Controllers\Security_Controller;
use Esteem_Email_Marketing\Libraries\Recipient_Service;
class Recipients extends Security_Controller {
 public function __construct(){parent::__construct();$this->access_only_admin();}
 public function preview(){ $f=$this->request->getPost();$svc=new Recipient_Service();return $this->response->setJSON(['count'=>$svc->count($f),'recipients'=>$svc->query($f,50,(int)($f['offset']??0))]); }
}
