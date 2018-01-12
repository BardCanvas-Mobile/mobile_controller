<?php

namespace hng2_modules\mobile_controller;

use hng2_base\account;
use hng2_base\device;
use hng2_media\media_record;

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
    
    /**
     * @param media_record         $item
     * @param media_processor_args $args
     *
     * @return null|string
     * @throws \Exception
     */
    public function resample_media($item, $args)
    {
        if( $item->type == "image" ) return $this->resample_image($item, $args);
        if( $item->type == "video" ) return $this->resample_video($item, $args);
        
        return null;
    }
    
    /**
     * @param media_record         $item
     * @param media_processor_args $args
     *
     * @return null|string
     * @throws \Exception
     */
    public function resample_image($item, $args)
    {
        global $config, $settings;
    
        include_once ROOTPATH . "/includes/guncs.php";
        
        $width = current(explode("x", $item->dimensions));
        if( $width < $args->max_image_width ) return null;
        
        $sourcefile = "{$config->datafiles_location}/uploaded_media/{$item->path}";
        $extension  = trim(strtolower(end(explode(".", $sourcefile))));
        $savepath   = dirname($sourcefile);
        $dimension  = THUMBNAILER_USE_WIDTH;
        $quality    = $extension == "png" ? 9 : $args->max_jpeg_quality;
        if( empty($quality) ) $quality = $settings->get("engine.thumbnail_jpg_compression");
        if( empty($quality) ) $quality = 90;
        
        try
        {
            $thumbnail = $extension == "png"
                       ? gfuncs_getmakePNGthumbnail($sourcefile, $savepath, $args->max_image_width, 0, $dimension, false, $quality, false, false)
                       : gfuncs_getmakethumbnail($sourcefile, $savepath, $args->max_image_width, 0, $dimension, false, $quality, false);
        }
        catch(\Exception $e)
        {
            return null;
        }
        
        return $thumbnail;
    }
    
    /**
     * @param media_record         $item
     * @param media_processor_args $args
     *
     * @return null|string
     * @throws \Exception
     */
    public function resample_video($item, $args)
    {
        global $config, $settings;
        
        $width = current(explode("x", $item->dimensions));
        if( empty($width) ) $width = $args->max_video_width;
        if( $width > $args->max_video_width ) $width = $args->max_video_width;
        
        $sourcefile  = "{$config->datafiles_location}/uploaded_media/{$item->path}";
        $parts       = explode(".", end(explode("/", $sourcefile)));
        $extension   = trim(strtolower(array_pop($parts)));
        $filename    = implode(".", $parts);
        $savepath    = dirname($sourcefile);
        $bitrate     = $args->max_video_bitrate;
        $targetfname = "{$filename}-{$width},{$bitrate}.{$extension}";
        $targetpath  = "{$savepath}/{$targetfname}";
        
        if( file_exists($targetpath) ) return $targetfname;
        
        $ffmpeg_bin  = rtrim($settings->get("engine.ffmpeg_path"), "/") . "/ffmpeg";
        $ffmpeg_args = "-i '{$sourcefile}' -vf scale={$width}:-2 -b:a 128k -ac 2 -b:v  {$bitrate} '{$targetpath}'";
        
        shell_exec("$ffmpeg_bin $ffmpeg_args > /dev/null 2>&1");
        if( ! file_exists($targetpath) ) return null;
        @chmod($targetpath, 0777);
        
        return $targetfname;
    }
}
