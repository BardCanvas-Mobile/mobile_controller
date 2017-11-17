<?php
namespace hng2_modules\mobile_controller;

class service
{
    /**
     * @var string Module name
     */
    public $module;
    
    /**
     * @var string Service keyname
     */
    public $key;
    
    /**
     * @var string Service type
     */
    public $type;
    
    /**
     * @var string Service icon label
     */
    public $label;
    
    /**
     * @var string enabled|disabled
     */
    public $status;
    
    public function __construct($module, $key, $type, $label, $status)
    {
        $this->module = $module;
        $this->key    = $key;
        $this->type   = $type;
        $this->label  = $label;
        $this->status = $status;
    }
    
    /**
     * Adds this service to the manifest - MUST BE OVERRIDEN!
     * 
     * @param object $manifest
     */
    public function forge( &$manifest )
    {
    }
}
