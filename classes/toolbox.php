<?php

namespace hng2_modules\mobile_controller;

use hng2_base\account;
use hng2_base\device;

class toolbox
{
    const VALID_OUTPUT_TYPES = "HTML,JSON";
    
    /**
     * @var string HTML|JSON
     */
    public $output_type = "HTML";
    
    public function throw_response($data)
    {
        if( is_string($data) ) $data = array("message" => $data);
        
        die( empty($_REQUEST["callback"])
            ? json_encode($data)
            : ( $_REQUEST["callback"] . "(" . json_encode($data) . ")" )
        );
    }
    
    public function throw_error($title, $contents = "", $show_retry_button = false)
    {
        if( $this->output_type == "JSON" ) $this->throw_response($title);
        
        global $config;
        
        $retry_button = $show_retry_button == false ? "" : "
            <p class=\"buttons-row\">
                <a class=\"button\" href=\"#\" onclick=\"BCapp.reloadAjaxifiedService(this)\">
                    Recargar
                </a>
            </p>
        ";
        
        if( $config->globals["raw_notifications"] )
            die("
                <span class='color-red'>$title</span><br>
                $contents
            ");
        
        die("
            <div class=\"content-block-title color-red\">
                <i class=\"fa fa-warning\"></i>
                $title
            </div>
            <div class=\"content-block\">
                <div class=\"content-block-inner\">
                    $contents
                </div>
                {$retry_button}
            </div>
        ");
    }
    
    public function render_content_block($contents, $title = "")
    {
        if( ! empty($title) ) echo "
            <div class=\"content-block-title\">$title</div>
        ";
        
        echo "
            <div class=\"content-block\">$contents</div>
        ";
    }
    
    /**
     * Debe llamarse en el bootstrap, antes de cargar la sesión desde cookies.
     */
    public function open_session()
    {
        global $database, $account, $config, $settings, $template, $modules;
        
        $current_module = $modules["mobile_controller"];
        
        if( ! empty($_REQUEST["bcm_platform"]) )
        {
            $config->globals["raw_notifications"] = $_REQUEST["bcm_underlaying_mode"] != "save";
            $config->globals["using_bcmobile"]    = true;
            $template->append("additional_body_attributes", " data-using-bcmobile='true'");
            
            setcookie( $settings->get("engine.user_session_cookie"), "", 0, "/", $config->cookies_domain );
            setcookie( $settings->get("engine.user_online_cookie"),  "", 0, "/", $config->cookies_domain );
            unset(
                $_COOKIE[$settings->get("engine.user_session_cookie")],
                $_COOKIE[$settings->get("engine.user_online_cookie")]
            );
        }
        
        $access_token = trim(stripslashes($_REQUEST["bcm_access_token"]));
        if( empty($access_token) ) return;
        
        $tkn = addslashes($access_token);
        $res = $database->query("select * from account_engine_prefs where name = 'bcm_device:$tkn' limit 1");
        if( $database->num_rows($res) == 0 )
            $this->throw_error(trim($current_module->language->authenticator->unknown_device));
        
        $row     = $database->fetch_object($res);
        $account = new account($row->id_account);
        if( $account->state != "enabled" )
            $this->throw_error(trim($current_module->language->authenticator->disabled_account));
        
        $device  = new device(json_decode($row->value));
        if( ! $device->_exists )          $this->throw_error(trim($current_module->language->authenticator->unknown_device));
        if( $device->state != "enabled" ) $this->throw_error(trim($current_module->language->authenticator->disabled_device));
        
        $device->ping();
    }
    
    /**
     * Returns an array of a pre-processed fake $_FILES collection.
     *
     * @return array
     * 
     * @throws \Exception
     */
    public function extract_embedded_media()
    {
        global $config;
        
        $return = array();
        foreach($_POST["embedded_attachments"] as $attachment)
        {
            list($type, $name, $mime, $tmp_name) = explode(";", $attachment);
            
            $target_dir = $config->datafiles_location . "/tmp";
            $tmp_name   = "$target_dir/$tmp_name";
            if( ! file_exists($tmp_name) )
                throw new \Exception("File $name not found.");
            
            if( ! isset($return[$type]) ) $return[$type] = array();
            
            $return[$type][] = array(
                "name"     => $name,
                "type"     => $mime,
                "tmp_name" => $tmp_name,
                "error"    => null,
                "size"     => filesize($tmp_name),
            );
        }
        
        return $return;
    }
}
