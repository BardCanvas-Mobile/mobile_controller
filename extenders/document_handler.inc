<?php
/**
 * Manifest delivery
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_modules\mobile_controller\service;

if( empty($_GET["handle"]) ) return;

$handle = trim(stripslashes($_GET["handle"]));
if( ! preg_match("/^bardcanvas_mobile.json/i", $handle) ) return;

$root_url = $settings->get("modules:mobile_controller.root_url");
if( substr($root_url, -1) != "/" ) $root_url .= "/";

$manifest = (object) array(
    "fullName"           => $settings->get("modules:mobile_controller.full_name"),
    "shortName"          => $settings->get("modules:mobile_controller.short_name"),
    "version"            => $settings->get("modules:mobile_controller.version"),
    "lastUpdate"         => $settings->get("modules:mobile_controller.last_update"),
    "rootURL"            => $root_url,
    "contactEmail"       => $settings->get("engine.webmaster_address"),
    "language"           => $settings->get("modules:mobile_controller.language_caption"),
    "company"            => $settings->get("modules:mobile_controller.company"),
    "companyPageURL"     => $settings->get("modules:mobile_controller.company_page_url"),
    "description"        => $settings->get("modules:mobile_controller.description"),
    "icon"               => $root_url . ltrim($settings->get("modules:mobile_controller.icon"), "/"),
    
    "disclaimer"         => $settings->get("modules:mobile_controller.disclaimer"),
    
    "loginRequired"      => $settings->get("modules:mobile_controller.login_required") == "true",
    "loginAuthenticator" => ltrim("{$current_module->get_url(false)}/authenticator.php", "/"),
    "userLevels"         => $config->user_levels_by_level,
    
    "timezoneOffset"     => strftime("%z"),
    
    "services"           => array(),
);

#region Services setup
#=====================

$config->globals["modules:mobile_controller.services_registry"] = array();
$current_module->load_extensions("services_registry", "definitions");
$services = $config->globals["modules:mobile_controller.services_registry"];

$raw_services = $settings->get("modules:mobile_controller.services_registry");
if( ! empty($raw_services) )
{
    $original_services = $services;
    $services          = array();
    $lines             = explode("\n", $raw_services);
    foreach($lines as $line)
    {
        $line = trim($line);
        
        list($module_name, $key, $type, $label, $status) = explode(" | ", $line);
    
        $module_name = trim($module_name);
        $key         = trim($key);
        $type        = trim($type);
        $label       = trim($label);
        $status      = trim($status);
        $class       = "hng2_modules\\$module_name\\service";
        
        $services["{$module_name}|{$key}"] = new $class(
            $module_name, $key, $type, $label, $status
        );
    }
    
    foreach($original_services as $key => $service)
        if( ! isset($services[$key]) )
            $services[$key] = $service;
}

/**
 * @var service[] $services
 */
foreach($services as $service)
{
    if( $service->status != "enabled" ) continue;
    if( ! $modules[$service->module]->enabled  ) continue;
    
    $service->forge($manifest);
}

#=========
#endregion

header("Content-Type: application/json; charset=utf-8");
die(json_encode($manifest));
