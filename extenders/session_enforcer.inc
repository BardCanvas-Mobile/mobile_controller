<?php
/**
 * Bootstrap session enforcer - overrides current opened session
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * Trailing vars:
 * @var module[] $modules
 * @var module   $module (self)
 * 
 * $_REQUEST params:
 * @param string "bcm_force_session" Must be "true" to proceed with session overriding
 * @param string "bcm_output_type"   Mandatory for proper output messages
 */

use hng2_base\account;
use hng2_base\module;
use hng2_modules\mobile_controller\toolbox;

if( $_REQUEST["bcm_minimalistic_mode_enforced"] == "true" )
{
    header("X-bcm_minimalistic_mode_enforced: {$_REQUEST["bcm_minimalistic_mode_enforced"]}");
    $account = new account();
    
    if( $_REQUEST["bcm_force_session"] != "true" ) return;
}

header("X-bcm_force_session: {$_REQUEST["bcm_force_session"]}");
header("X-bcm_output_type: {$_REQUEST["bcm_output_type"]}");
header("X-bcm_platform: {$_REQUEST["bcm_platform"]}");
header("X-bcm_version: {$_REQUEST["bcm_version"]}");
header("X-bcm_access_token: {$_REQUEST["bcm_access_token"]}");

if( $_REQUEST["bcm_force_session"] != "true" ) return;
if( ! in_array($_REQUEST["bcm_output_type"], explode(",", toolbox::VALID_OUTPUT_TYPES)) ) return;

header("X-pre-bcm-user: " . ($account->_exists ? "(undefined)" : $account->user_name));
$toolbox = new toolbox();
$toolbox->output_type = $_REQUEST["bcm_output_type"];
$toolbox->open_session();
header("X-post-bcm-user: {$account->user_name}");
