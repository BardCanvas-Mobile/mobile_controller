<?php
/**
 * Manifest delivery
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_modules\mobile_controller\action;
use hng2_modules\mobile_controller\service;

if( empty($_GET["handle"]) ) return;

$handle = trim(stripslashes($_GET["handle"]));
if( ! preg_match("/^bardcanvas_mobile.json/i", $handle) ) return;

if( empty($modules["mobile_controller"]) ) throw_fake_404();
if( ! $modules["mobile_controller"]->enabled ) throw_fake_404();
if( $settings->get("modules:mobile_controller.services_engine_enabled") != "true" ) throw_fake_404();

$root_url = $settings->get("modules:mobile_controller.root_url");
if( substr($root_url, -1) != "/" ) $root_url .= "/";

$manifest = (object) array(
    "fullName"           => $settings->get("modules:mobile_controller.full_name"),
    "shortName"          => $settings->get("modules:mobile_controller.short_name"),
    "version"            => $settings->get("modules:mobile_controller.version"),
    "lastUpdate"         => $settings->get("modules:mobile_controller.last_update"),
    "rootURL"            => $root_url,
    
    "attachmentsReceiverURL" => $this_module->get_url(true) . "/scripts/receive_attachment.php",
    
    "contactEmail"       => $settings->get("engine.webmaster_address"),
    "language"           => $settings->get("modules:mobile_controller.language_caption"),
    "company"            => $settings->get("modules:mobile_controller.company"),
    "companyPageURL"     => $settings->get("modules:mobile_controller.company_page_url"),
    "description"        => $settings->get("modules:mobile_controller.description"),
    "icon"               => $root_url . ltrim($settings->get("modules:mobile_controller.icon"), "/"),
    
    "disclaimer"         => $settings->get("modules:mobile_controller.disclaimer"),
    "documentLinks"      => array(),
    
    "loginRequired"      => $settings->get("modules:mobile_controller.login_required") == "true",
    "loginAuthenticator" => ltrim("{$current_module->get_url(false)}/authenticator.php", "/"),
    
    "hasFacebookLogin"   => $settings->get("modules:single_signon.facebook_app_id")     != "" &&
                            $settings->get("modules:single_signon.facebook_app_secret") != "",
    
    "facebookLoginChecker" => $this_module->get_url(true) . "/facebook_authenticator.php",
    
    "userLevels"         => $config->user_levels_by_level,
    
    "timezoneOffset"     => strftime("%z"),
    
    "services"           => array(),
    "actionsRegistry"    => array(),
);

#region Document Links setup
#===========================

$raw_document_links = $settings->get("modules:mobile_controller.document_links");
if( ! empty($raw_document_links) )
{
    foreach(explode("\n", $raw_document_links) as $line)
    {
        list($caption, $slug) = preg_split('/\s+-\s+/', $line);
        $caption = trim($caption);
        $link    = trim($slug);
        if( substr($link, 0, 1) == "/" )        $link = $root_url . ltrim($link, "/");
        elseif( substr($link, 0, 4) != 'http' ) $link = $root_url . $link;
        
        if( stristr($link, "?") === false ) $link .= "?bcm_minimalistic_mode_enforced=true";
        else                                $link .= "&bcm_minimalistic_mode_enforced=true";
        
        $manifest->documentLinks[] = (object) array(
            "caption" => $caption,
            "url"     => $link,
        );
    }
}

#=========
#endregion

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

#region Actions Registry setup
#=============================

# Disable account
$manifest->actionsRegistry["accounts:disable"] = new action(array(
    "id"          => "accounts:disable",
    "module_name" => "accounts",
    "script_url"  => "{$modules["accounts"]->get_url(true)}/scripts/toolbox.php?mode=disable",
    "options"     => array(
        "requires_confirmation" => true,
        "confirmation_message"  => unindent($this_module->language->messages->prompts->disable_account),
        "success_notification"  => unindent($this_module->language->messages->success_notifications->disable_account),
    ),
));

if( $modules["messaging"]->enabled )
{
    # Send PM
    $manifest->actionsRegistry["messaging:compose"] = new action(array(
        "id"          => "messaging:compose",
        "module_name" => "messaging",
        "script_url"  => "{$modules["messaging"]->get_url(true)}/send_pm.php",
        "call_method" => "posting_form_composer",
        "options"     => (object) array(
            "composer" => (object) array(
                "title" => trim($this_module->language->remote_pm_composer->title),
                "fields" => (object) array(
                    "target" => (object) array(
                        "type" => "hidden",
                    ),
                    "target_name" => (object) array(
                        "type"       => "text",
                        "title"      => trim($this_module->language->remote_pm_composer->target_name->caption),
                        "attributes" => "readonly",
                    ),
                    "content" => (object) array(
                        "type"        => "textarea/expandible",
                        "placeholder" => trim($this_module->language->remote_pm_composer->content->placeholder),
                    ),
                    "attachments" => (object) array(
                        "type"        => "local_image_selector",
                        "title"       => trim($this_module->language->remote_pm_composer->attachments->title),
                    ),
                )
            )
        ),
    ));
}

$this_module->load_extensions("actions_registry", "definitions");

#=========
#endregion

header("Content-Type: application/json; charset=utf-8");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

die(json_encode( $manifest ));
