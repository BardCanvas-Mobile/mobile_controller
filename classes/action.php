<?php
namespace hng2_modules\mobile_controller;

use hng2_repository\abstract_record;

class action extends abstract_record
{
    public $id = "";
    
    public $module_name = "";
    
    public $script_url = "";
    
    public $call_method = "get";
    
    public function set_new_id() {}
}
