<?php
/**
 * Account authenticator
 *
 * @package mobile_controller
 * @author  Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @param string "username"
 * @param string "password" MD5 hash
 * @param string "device"
 * @param string "callback" Optional, for AJAX call
 * 
 * @returns string JSON {message:string, data:mixed}
 */

use hng2_base\account;
use hng2_base\device;
use hng2_modules\mobile_controller\toolbox;

include "../config.php";
include "../includes/bootstrap.inc";
header("Content-Type: application/json; charset=utf-8");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

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

$account = new account($_REQUEST["username"]);

if( ! $account->_exists )
    $toolbox->throw_response(trim($current_module->language->authenticator->account_not_found));

if( $account->password != $_REQUEST["password"] )
    $toolbox->throw_response(trim($current_module->language->authenticator->invalid_password));

if( $account->state != "enabled" )
    $toolbox->throw_response(trim($current_module->language->authenticator->account_disabled));

$meta = array();
$current_module->load_extensions("authenticator", "after_meta_init");

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

/** @noinspection PhpUnhandledExceptionInspection */
$database->exec("
    insert ignore into account_logins set
    `id_account` = '$account->id_account',
    `id_device`  = '$device_record->id_device',
    `login_date` = '".date("Y-m-d H:i:s")."',
    `ip`         = '".get_remote_address()."',
    `hostname`   = '".gethostbyaddr(get_remote_address())."',
    `location`   = '".forge_geoip_location(get_remote_address())."'
");

$meta["user_level"] = (int) $account->level;
$meta["avatar_url"] = $account->get_avatar_url(true);

$toolbox->throw_response(array(
    "message" => "OK",
    "data"    => array(
        "display_name" => $account->display_name,
        "access_token" => md5($device_record->id_device),
        "meta"         => $meta,
    )
));
