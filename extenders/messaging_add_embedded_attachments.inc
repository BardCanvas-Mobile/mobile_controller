<?php
/**
 * Messaging extender
 * Called from messaging/send_pm.php
 * Adds attached media to the message contents.
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var module[] $modules
 */

use hng2_base\module;
use hng2_modules\mobile_controller\toolbox;

if( empty($_POST["embedded_attachments"]) ) return;
if( ! is_array($_POST["embedded_attachments"]) ) return;

global $modules, $account, $contents;

$toolbox          = new toolbox();
$messaging_module = $modules["messaging"];

try
{
    $uploads = $toolbox->extract_embedded_media();
}
catch(\Exception $e)
{
    die( $e->getMessage() );
}

if( empty($uploads) ) return;


$target_dir = "{$config->datafiles_location}/pm_attachments/{$account->user_name}";
if( ! is_dir($target_dir) )
{
    if( ! @mkdir($target_dir, 0777, true) )
        die(replace_escaped_vars($messaging_module->language->messages->cannot_create_dir, '{$dir}', $target_dir));
    
    @chmod($target_dir, 0777);
}

foreach($uploads["image"] as $upload)
{
    $parts       = explode(".", $upload["name"]);
    $extension   = strtolower(array_pop($parts));
    $name        = wp_sanitize_filename(implode(".", $parts));
    $target_file = "$target_dir/$name.$extension";
    $date        = date("Ymd-His");
    
    if( ! in_array($extension, array("png", "jpg", "jpeg", "gif")) )
        die($messaging_module->language->messages->invalid_pm_attachment);
    
    if( file_exists($target_file) )
        $target_file = "$target_dir/$name-$date.$extension";
    
    if( ! @rename($upload["tmp_name"], $target_file) )
        die(replace_escaped_vars(
            $messaging_module->language->messages->cannot_move_attachment,
            array('{$file}', '{$target}'),
            array($upload["name"], $target_file)
        ));
    
    $target_url = "{$config->full_root_path}/pm_attachments/{$account->user_name}/" . basename($target_file);
    $contents .= "\n\n<img class='pm_attachment' src='$target_url'>";
}

$contents = trim($contents);
