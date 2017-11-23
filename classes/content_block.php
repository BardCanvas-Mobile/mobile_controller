<?php
namespace hng2_modules\mobile_controller;

use hng2_repository\abstract_record;

class content_block extends abstract_record
{
    public $title = "";
    
    public $class = "";
    
    public $contents = "";
    
    public function set_new_id() {}
}
