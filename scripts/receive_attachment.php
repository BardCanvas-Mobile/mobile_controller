<?php
/**
 * Attachment receiver
 * 
 * @package mobile_controller
 * @author  Alejandro Caballero - lava.caballero@gmail.com
 * 
 * @param string "username"
 * @param string "password" MD5 hash
 * @param string "device"
 * @param string "callback" Optional, for AJAX call
 * 
 * @returns string "OK" or error.
 */

use hng2_modules\mobile_controller\toolbox;

include "../../config.php";
include "../../includes/bootstrap.inc";
header("Content-Type: text/plain; charset=utf-8");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$toolbox = new toolbox();
$toolbox->output_type = "HTML";
$toolbox->open_session();
if( ! $account->_exists ) throw_fake_401();
if( $account->state != "enabled" ) throw_fake_401();

if( empty($_POST) ) die(trim($current_module->language->messages->missing_params));
if( empty($_POST["target_name"]) ) die(trim($current_module->language->messages->missing_params));
if( empty($_FILES) ) die(trim($current_module->language->messages->no_attachments_received));
if( ! is_array($_FILES) ) die(trim($current_module->language->messages->no_attachments_received));
if( ! is_array($_FILES["file"]) ) die(trim($current_module->language->messages->no_attachments_received));

$file = $_FILES["file"];
if( ! is_uploaded_file($file["tmp_name"]) ) die(trim($current_module->language->messages->invalid_file_uploaded));

$ext = end(explode(".", $file["name"]));
if( in_array($ext, array("exe","com","bin","sys","cmd","bat","wsf","cgi","sh","pl","php","asp","aspx","jar")) )
    die(trim($current_module->language->messages->file_type_rejected));

$target_dir = $config->datafiles_location . "/tmp";
if( ! is_dir($target_dir) )
{
    if( ! @mkdir($target_dir) )
        die(trim($current_module->language->messages->cannot_create_temp_dir));
    
    @chmod($target_dir, 0777);
}

$target_file = $target_dir . "/" . stripslashes($_POST["target_name"]);
if( ! move_uploaded_file($file["tmp_name"], $target_file) )
    die(trim($current_module->language->messages->cannot_move_file));

@chmod($target_file, 0777);
echo "OK";
