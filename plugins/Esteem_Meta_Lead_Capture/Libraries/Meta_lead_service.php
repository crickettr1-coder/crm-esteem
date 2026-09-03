<?php
namespace Esteem_Meta_Lead_Capture\Libraries;

class Meta_lead_service {
    private $db;
    private $prefix;
    public function __construct() { $this->db = db_connect('default'); $this->prefix = $this->db->getPrefix(); }
    private function table($name) { return $this->prefix . $name; }
    public function log_rejection($status, $hash) { $this->db->table($this->table('meta_lead_capture_audit'))->insert(array('event_type'=>'rejected','payload_hash'=>$hash,'status'=>$status,'created_at'=>get_current_utc_time())); }
    public function settings() {
        $rows = $this->db->table($this->table('meta_lead_capture_settings'))->get()->getResult(); $out = array();
        foreach ($rows as $row) $out[$row->setting_name] = $row->setting_value;
        foreach (array('enabled'=>'0','app_id'=>'','app_secret'=>'','page_id'=>'','page_access_token'=>'','verify_token'=>'','api_version'=>'','default_status'=>'New','owner_ids'=>'') as $key=>$default) if (!array_key_exists($key,$out)) $out[$key]=$default;
        foreach (array('app_secret','page_access_token','verify_token') as $key) $out[$key] = esteem_meta_setting_unsecret($out[$key], $key);
        return $out;
    }
    public function save_settings($data) {
        foreach ($data as $key=>$value) {
            if (in_array($key, array('app_secret','page_access_token','verify_token'), true)) $value = esteem_meta_setting_secret($value, $key);
            $this->db->table($this->table('meta_lead_capture_settings'))->replace(array('setting_name'=>$key,'setting_value'=>(string)$value));
        }
    }
    public function graph_get($path, $settings) {
        $version = trim($settings['api_version']); $token = $settings['page_access_token'];
        if (!preg_match('/^v[0-9]+(?:\.[0-9]+)?$/', $version) || !$token) return array('ok'=>false,'error'=>'Meta API version and Page access token are required.');
        $url = 'https://graph.facebook.com/' . rawurlencode($version) . '/' . ltrim($path, '/');
        $ch = curl_init($url); curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_HTTPHEADER=>array('Authorization: Bearer '.$token, 'Accept: application/json', 'User-Agent: Esteem-CRM-MetaLeadCapture/1.0')));
        $body = curl_exec($ch); $error = curl_error($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $json = json_decode((string)$body, true);
        return array('ok'=>!$error && $code >= 200 && $code < 300 && is_array($json) && empty($json['error']), 'code'=>$code, 'data'=>$json, 'error'=>$error ?: (isset($json['error']['message']) ? $json['error']['message'] : 'Meta Graph API request failed.'));
    }
    public function process($meta_id, $page_id, $form_id, $event, $payload) {
        $settings = $this->settings(); $now = get_current_utc_time(); $audit = $this->table('meta_lead_capture_audit');
        $insert = $this->db->table($audit)->insert(array('meta_lead_id'=>$meta_id,'page_id'=>$page_id,'form_id'=>$form_id,'event_type'=>'leadgen','payload_hash'=>hash('sha256', json_encode($payload)),'status'=>'received','created_at'=>$now));
        if (!$insert) return array('ok'=>true,'duplicate_event'=>true);
        $audit_id = $this->db->insertID();
        if ((string)$settings['enabled'] !== '1') { $this->finish($audit_id,'disabled'); return array('ok'=>true,'disabled'=>true); }
        $lead = $this->graph_get($meta_id . '?fields=id,created_time,field_data,form_id', $settings);
        if (!$lead['ok']) { $this->finish($audit_id,'rejected',$lead['error']); return array('ok'=>false,'error'=>'Meta lead retrieval failed.'); }
        $mapped = $this->map_fields($lead['data']);
        $duplicate = $this->find_duplicate($mapped['phone'], $mapped['email']);
        if ($duplicate) { $this->db->table($this->table('meta_lead_capture_mapping'))->insert(array('meta_lead_id'=>$meta_id,'page_id'=>$page_id,'form_id'=>$form_id,'crm_client_id'=>$duplicate['id'],'normalized_phone'=>$mapped['phone'],'normalized_email'=>$mapped['email'],'status'=>'duplicate','raw_field_data'=>json_encode($mapped['raw']),'created_at'=>$now)); $this->finish($audit_id,'duplicate','Existing CRM record: '.$duplicate['id'],$duplicate['id'],'client'); return array('ok'=>true,'duplicate'=>true,'crm_id'=>$duplicate['id']); }
        $mapping_table = $this->table('meta_lead_capture_mapping');
        if (!$this->db->table($mapping_table)->insert(array('meta_lead_id'=>$meta_id,'page_id'=>$page_id,'form_id'=>$form_id,'normalized_phone'=>$mapped['phone'],'normalized_email'=>$mapped['email'],'status'=>'processing','raw_field_data'=>json_encode($mapped['raw']),'created_at'=>$now))) { $this->finish($audit_id,'duplicate_event'); return array('ok'=>true,'duplicate_event'=>true); }
        $owner = $this->next_owner($settings['owner_ids']); if (!$owner) { $this->finish($audit_id,'rejected','No valid round-robin owner is configured.'); return array('ok'=>false,'error'=>'No valid round-robin owner is configured.'); }
        $status = $this->status_id($settings['default_status']); $source = $this->source_id();
        if (!$status || !$source) { $this->finish($audit_id,'rejected','Lead status or source is unavailable.'); return array('ok'=>false,'error'=>'Lead status or source is unavailable.'); }
        $client = array('company_name'=>$mapped['name'] ?: 'Meta lead '.$meta_id,'type'=>'person','city'=>$mapped['city'],'state'=>$mapped['state'],'zip'=>$mapped['postcode'],'country'=>'Australia','phone'=>$mapped['phone'],'is_lead'=>1,'lead_status_id'=>$status,'lead_source_id'=>$source,'owner_id'=>$owner,'created_by'=>$owner,'created_date'=>$now,'starred_by'=>'','group_ids'=>'','last_lead_status'=>'','labels'=>'','managers'=>'');
        $client_id = model('App\\Models\\Clients_model')->ci_save($client);
        if (!$client_id) { $this->finish($audit_id,'rejected','CRM lead creation failed.'); return array('ok'=>false,'error'=>'CRM lead creation failed.'); }
        $this->add_contact($client_id, $mapped);
        $this->add_note($client_id, 'Imported from Meta Lead Ads', 'Meta lead ID: '.$meta_id.'\nForm ID: '.($form_id ?: 'Unknown'));
        $this->db->table($mapping_table)->where('meta_lead_id',$meta_id)->update(array('crm_client_id'=>$client_id,'status'=>'created'));
        $this->finish($audit_id,'created',null,$client_id,'client');
        return array('ok'=>true,'created'=>true,'crm_id'=>$client_id);
    }
    private function finish($id,$status,$error=null,$record=0,$type=null) { $this->db->table($this->table('meta_lead_capture_audit'))->where('id',$id)->update(array('status'=>$status,'error_message'=>$error,'crm_record_id'=>$record,'crm_record_type'=>$type,'processed_at'=>get_current_utc_time())); }
    public function map_fields($lead) {
        $raw = array(); foreach ((array)($lead['field_data'] ?? array()) as $item) { $key = strtolower(trim((string)($item['name'] ?? ''))); $value = $item['values'][0] ?? ''; $raw[$key] = is_scalar($value) ? trim((string)$value) : $value; }
        $get = function($names) use ($raw) { foreach ($names as $name) if (isset($raw[$name]) && $raw[$name] !== '') return $raw[$name]; return ''; };
        $first=$get(array('first_name','firstname')); $last=$get(array('last_name','lastname')); $name=$get(array('full_name','name')) ?: trim($first.' '.$last);
        $known=array('full_name','name','first_name','firstname','last_name','lastname','phone_number','phone','email','postcode','zip','state','suburb','city','solar_interest','battery_interest','solar_or_battery_interest','marketing_consent','consent');
        $unknown=array(); foreach($raw as $k=>$v) if(!in_array($k,$known,true)) $unknown[$k]=$v;
        return array('name'=>$name,'first_name'=>$first,'last_name'=>$last,'phone'=>esteem_meta_normalize_phone($get(array('phone_number','phone'))),'email'=>strtolower(trim((string)$get(array('email')))),'postcode'=>$get(array('postcode','zip')),'state'=>$get(array('state')),'city'=>$get(array('suburb','city')),'interest'=>$get(array('solar_or_battery_interest','solar_interest','battery_interest')),'consent'=>$get(array('marketing_consent','consent')),'raw'=>$raw,'unknown'=>$unknown);
    }
    private function find_duplicate($phone,$email) {
        $clients=$this->table('clients'); $users=$this->table('users'); $rows=$this->db->query("SELECT c.id,c.phone,u.phone AS contact_phone,u.email FROM $clients c LEFT JOIN $users u ON u.client_id=c.id AND u.is_primary_contact=1 WHERE c.deleted=0")->getResult();
        foreach($rows as $row) { if($phone && ($phone===esteem_meta_normalize_phone($row->phone) || $phone===esteem_meta_normalize_phone($row->contact_phone))) return array('id'=>(int)$row->id); if($email && $email===strtolower(trim((string)$row->email))) return array('id'=>(int)$row->id); } return null;
    }
    private function status_id($title) { $row=$this->db->query('SELECT id FROM '.$this->table('lead_status').' WHERE deleted=0 AND LOWER(title)=? LIMIT 1',array(strtolower(trim($title ?: 'New'))))->getRow(); return $row ? (int)$row->id : 0; }
    private function source_id() { $name='Facebook / Instagram Lead Ads'; $row=$this->db->query('SELECT id FROM '.$this->table('lead_source').' WHERE deleted=0 AND title=? LIMIT 1',array($name))->getRow(); if($row)return (int)$row->id; $this->db->table($this->table('lead_source'))->insert(array('title'=>$name,'sort'=>999,'deleted'=>0)); return (int)$this->db->insertID(); }
    private function next_owner($ids) { $ids=array_values(array_filter(array_map('intval',explode(',',(string)$ids)))); if(!$ids)return 0; $placeholders=implode(',',array_fill(0,count($ids),'?')); $rows=$this->db->query('SELECT id FROM '.$this->table('users').' WHERE id IN ('.$placeholders.') AND deleted=0 AND status="active" AND user_type="staff" ORDER BY FIELD(id,'.$placeholders.')',array_merge($ids,$ids))->getResultArray(); $valid=array_map('intval',array_column($rows,'id')); if(!$valid)return 0; $state=$this->db->query('SELECT last_owner_id FROM '.$this->table('meta_lead_capture_round_robin').' WHERE id=1')->getRow(); $last=$state?(int)$state->last_owner_id:0; $index=array_search($last,$valid,true); $owner=$valid[($index===false?0:($index+1)%count($valid))]; $this->db->table($this->table('meta_lead_capture_round_robin'))->where('id',1)->update(array('last_owner_id'=>$owner,'updated_at'=>get_current_utc_time())); return $owner; }
    private function add_contact($client_id,$m) { model('App\\Models\\Users_model')->ci_save(array('client_id'=>$client_id,'user_type'=>'client','is_primary_contact'=>1,'client_permissions'=>'all','first_name'=>$m['first_name'] ?: $m['name'],'last_name'=>$m['last_name'],'email'=>$m['email'],'phone'=>$m['phone'],'status'=>'active','disable_login'=>1,'created_at'=>get_current_utc_time())); }
    private function add_note($client_id,$title,$description) { model('App\\Models\\Notes_model')->ci_save(array('created_by'=>1,'created_at'=>get_current_utc_time(),'title'=>$title,'description'=>$description,'client_id'=>$client_id,'project_id'=>0,'user_id'=>0,'labels'=>'','files'=>'a:0:{}','is_public'=>0,'deleted'=>0,'category_id'=>0,'color'=>'#4a8af4')); }
}
