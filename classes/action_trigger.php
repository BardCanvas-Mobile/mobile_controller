<?php
namespace hng2_modules\mobile_controller;

use hng2_repository\abstract_record;

class action_trigger extends abstract_record
{
    public $action_id = "";
    
    public $caption = "";
    
    public $icon = "";
    
    public $class = "";
    
    /**
     * @var array key=>value pairs of action options
     */
    public $options = array();
    
    /**
     * @var array key=>value pairs of params to pass to the action being triggered
     */
    public $params = array();
    
    public function set_new_id() {}
}
