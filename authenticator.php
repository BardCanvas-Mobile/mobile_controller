<?php
/**
 * Account authenticator
 *
 * @package mobile_controller
 * @author  Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @var module[] $modules
 * 
 * @param string "username"
 * @param string "password" MD5 hash
 * @param string "device"
 * @param string "tfa_code"
 * @param string "callback" Optional, for AJAX call
 * 
 * @returns string JSON {message:string, data:mixed}
 */

use hng2_base\account;
use hng2_base\device;
use hng2_base\module;
use hng2_modules\mobile_controller\toolbox;

include "../config.php";
include "../includes/bootstrap.inc";
header("Content-Type: application/json; charset=utf-8");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

include_once ROOTPATH . "/accounts/2fa/totp.php";

$toolbox = new toolbox();

if( empty($_REQUEST["username"]) )
    $toolbox->throw_response(trim($current_module->language->authenticator->no_username_provided));

if( is_numeric($_REQUEST["username"]) )
    $toolbox->throw_response(trim($current_module->language->authenticator->invalid_username_provided));

if( preg_match('/[^a-zA-z0-9\-_]/', $_REQUEST["username"]) )
    $toolbox->throw_response(trim($current_module->language->authenticator->invalid_username_provided));

if( empty($_REQUEST["password"]) )
    $toolbox->throw_response(trim($current_module->language->authenticator->no_password_provided));

if( empty($_REQUEST["device"]) )
    $toolbox->throw_response(trim($current_module->language->authenticator->no_device_id_provided));

if( strlen($_REQUEST["password"]) != 32 )
    $toolbox->throw_response(trim($current_module->language->authenticator->invalid_password_provided));

try
{
    check_sql_injection($_REQUEST["username"]);
}
catch(\Exception $e)
{
    $toolbox->throw_response($e->getMessage());
}

$account = new account($_REQUEST["username"]);

if( ! $account->_exists )
    $toolbox->throw_response(trim($current_module->language->authenticator->account_not_found));

if( $account->password != $_REQUEST["password"] )
    $toolbox->throw_response(trim($current_module->language->authenticator->invalid_password));

if( $account->state != "enabled" )
    $toolbox->throw_response(trim($current_module->language->authenticator->account_disabled));

#
# IPs whitelist checks
#

$ips_whitelist = $account->engine_prefs["@accounts:ips_whitelist"];
if( ! empty($ips_whitelist) )
{
    $ip    = get_user_ip();
    $lines = explode("\n", $ips_whitelist);
    $found = false;
    foreach($lines as $line)
    {
        $listed_ip = trim($line);
        if( empty($listed_ip) ) continue;
        if( substr($listed_ip, 0, 1) == "#" ) continue;
        
        if( stristr($listed_ip, "*") )
        {
            $pattern = str_replace(".", "\\.", $listed_ip);
            $pattern = str_replace("*", ".*",  $pattern);
            
            if(preg_match("/$pattern/", $ip) )
            {
                $found = true;
                break;
            }
        }
        else
        {
            if( $ip == $listed_ip )
            {
                $found = true;
                break;
            }
        }
    }
    
    if( ! $found )
    {
        $modules["accounts"]->load_extensions("login", "after_whitelist_check_fail");
        $toolbox->throw_response(trim(
            $modules["accounts"]->language->errors->ip_not_in_whitelist
        ));
    }
}

#
# 2FA checkup
#

$device_string = stripslashes($_REQUEST["device"]);
if( $settings->get("modules:accounts.use_2fa") == "true" && ! empty($account->engine_prefs["@accounts:2fa_secret"]) )
{
    if( ! preg_match("@bardcanvas mobile/([0-9.]+)@i", $device_string, $matches) )
    {
        $toolbox->throw_response(trim($current_module->language->authenticator->tfa_required->invalid_client));
    }
    else
    {
        $client_version = $matches[1];
        if( version_compare($client_version, "1.1.2") < 0 )
        {
            $toolbox->throw_response(trim($current_module->language->authenticator->tfa_required->older_client));
        }
        
        $tfa_code = trim(stripslashes($_REQUEST["tfa_code"]));
        if( ! is_numeric($tfa_code) ) $toolbox->throw_response("@ASK_FOR_2FA");
        
        $enc_secret = $account->engine_prefs["@accounts:2fa_secret"];
        $raw_secret = three_layer_decrypt($enc_secret, $config->website_key, $account->id_account, $account->creation_date);
        $totp       = new totp();
        $res        = $totp->verifyCode($raw_secret, $tfa_code);
        if( ! $res ) $toolbox->throw_response(trim($current_module->language->authenticator->tfa_required->wrong_code));
    }
}

$meta = array();
$current_module->load_extensions("authenticator", "after_meta_init");

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
$modules["accounts"]->load_extensions("login", "before_inserting_login_record");

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

$toolbox->throw_response(array(
    "message" => "OK",
    "data"    => array(
        "user_name"    => $account->user_name,
        "display_name" => $account->display_name,
        "access_token" => md5($device_record->id_device),
        "meta"         => $meta,
    )
));
