<?php
/**
 * Remote Facebook authentication - Both page and helper by JSON.
 * 
 * @package mobile_controller
 * @author  Alejandro Caballero - lava.caballero@gmail.com
 * 
 * $_GET params:
 * @param string "mode"   See below.
 * @param string "device" Mandatory for HTML modes (see below).
 * @param string "token"  Temporary token to set credentials to.
 * 
 * Working modes:
 * • background_check: When provided, it will return the authenticated token and user meta for the website.
 *                     Note: this mode implies a JSON response!
 * • confirm:          Outputs a confirmation message and asks for the browser to be closed.
 * • (none):           Outputs the login form. This is the default mode.
 */

use hng2_base\device;

include "../config.php";
include "../includes/bootstrap.inc";

#
# Inits
#

$incoming_token  = trim(stripslashes($_GET["token"]));
$incoming_device = trim(stripslashes($_GET["device"]));
$temp_save_path  = "{$config->datafiles_location}/tmp";

if( ! is_dir($temp_save_path) ) @mkdir($temp_save_path, 0777);

#
# Background Check mode
#

if( $_GET["mode"] == "background_check" )
{
    header("Content-Type: application/json; charset=utf-8");
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    
    if( $incoming_token == "" ) die(json_encode(array("message" => trim(
        $current_module->language->facebook_authenticator->empty_token->raw_info
    ))));
    
    $target_file = sprintf("%s/bcm_%s.token", $temp_save_path, $incoming_token);
    if( ! file_exists($target_file) ) die(json_encode(array("message" => "ERROR:FILE_NOT_FOUND")));
    
    $data = json_decode(file_get_contents($target_file));
    if( empty($data) ) die(json_encode(array("message" => trim(
        $current_module->language->facebook_authenticator->empty_data
    ))));
    
    @unlink($target_file);
    die(json_encode(array("message" => "OK", "data" => $data)));
}

#
# Standard/confirmation mode validations
#

if( empty($incoming_token) )
{
    $template->set_page_title($current_module->language->facebook_authenticator->page_title);
    $template->set("error_page_title", $current_module->language->facebook_authenticator->empty_token->title);
    $template->set("error_page_content", replace_escaped_objects(
        $current_module->language->facebook_authenticator->empty_token->info,
        array('{$root_path}' => $config->full_root_path)
    ));
    $template->page_contents_include = "error_page.inc";
    include "{$template->abspath}/popup.php";
    
    exit;
}

if( empty($incoming_device) )
{
    $template->set_page_title($current_module->language->facebook_authenticator->page_title);
    $template->set("error_page_title", $current_module->language->facebook_authenticator->missing_device->title);
    $template->set("error_page_content", replace_escaped_objects(
        $current_module->language->facebook_authenticator->missing_device->info,
        array('{$root_path}' => $config->full_root_path)
    ));
    $template->page_contents_include = "error_page.inc";
    include "{$template->abspath}/popup.php";
    
    exit;
}

#
# Confirmation mode
#

if( $_GET["mode"] == "confirm" )
{
    $meta          = array();
    $device_string = stripslashes($_REQUEST["device"]);
    $device_record = new device($account->id_account, $device_string);
    if( $device_record->_exists )
    {
        $device_record->ping();
    }
    else
    {
        $device_record->set_new_id();
        $device_record->id_account    = $account->id_account;
        $device_record->device_label  = "N/A";
        $device_record->device_header = $device_string;
        $device_record->state         = "enabled";
        $device_record->save();
    }
    
    $device_hash     = md5($device_record->id_device);
    $device_pref_key = "bcm_device:{$device_hash}";
    if( empty($account->engine_prefs[$device_pref_key]) )
        $account->set_engine_pref($device_pref_key, $device_record->id_device);
    
    $config->globals["@accounts:account_id_logging_in"] = $account->id_account;
    $modules["accounts"]->load_extensions("login", "after_inserting_login_record");
    
    $ip = get_remote_address();
    
    $database->exec("
    insert ignore into account_logins set
    `id_account` = '$account->id_account',
    `id_device`  = '$device_record->id_device',
    `login_date` = '".date("Y-m-d H:i:s")."',
    `ip`         = '$ip',
    `hostname`   = '".@gethostbyaddr($ip)."',
    `location`   = '".addslashes(get_geoip_location_with_isp($ip))."'
    ");
    
    $meta["user_level"] = (int) $account->level;
    $meta["avatar_url"] = $account->get_avatar_url(true);
    
    $data = json_encode(array(
        "user_name"    => $account->user_name,
        "display_name" => $account->display_name,
        "access_token" => md5($device_record->id_device),
        "meta"         => $meta,
    ));
    
    $target_file = sprintf("%s/bcm_%s.token", $temp_save_path, $incoming_token);
    if( ! @file_put_contents($target_file, $data) )
    {
        $template->set_page_title($current_module->language->facebook_authenticator->page_title);
        $template->set("error_page_title", $current_module->language->facebook_authenticator->error_writing_file->title);
        $template->set("error_page_content", replace_escaped_objects(
            $current_module->language->facebook_authenticator->error_writing_file->info,
            array('{$root_path}' => $config->full_root_path)
        ));
        $template->page_contents_include = "error_page.inc";
        include "{$template->abspath}/popup.php";
        
        exit;
    }
    
    $template->set_page_title($current_module->language->facebook_authenticator->page_title);
    $template->page_contents_include = "confirmation_page.inc";
    include "{$template->abspath}/popup.php";
    
    exit;
}

#
# Standard mode (login form)
#

$template->set_page_title($current_module->language->facebook_authenticator->page_title);
$template->page_contents_include = "authentication_page.inc";
include "{$template->abspath}/popup.php";
