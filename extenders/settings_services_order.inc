<?php
/**
 * Settings order editor
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * Trailing globals:
 * @var module[] $modules
 * @var module   $current_module settings
 * @var module   $this_module    self
 */

use hng2_base\module;

global $config;

/** @var array $params [name => string, value => string, options => SimpleXMLElement] */
$params = $config->globals["specs_renderer/services_order"];

$config->globals["modules:mobile_controller.services_registry"] = array();
$this_module->load_extensions("services_registry", "definitions");
$services = $config->globals["modules:mobile_controller.services_registry"];

if( empty($params["value"]) )
{
    $lines = array();
    foreach($services as $data)
        $lines[] = sprintf("%s | %s | %s | %s | %s", $data->module, $data->key, $data->type, $data->label, $data->status);
    $params["value"] = implode("\n", $lines);
}
else
{
    $original_services = $services;
    $services          = array();
    $lines             = explode("\n", $params["value"]);
    foreach($lines as $line)
    {
        $line = trim($line);
        
        list($module_name, $key, $type, $label, $status) = explode(" | ", $line);
        
        $module_name = trim($module_name);
        $key         = trim($key);
        $type        = trim($type);
        $label       = trim($label);
        $status = trim($status);
        
        $services["{$module_name}:{$key}"] = (object) array(
            "module" => $module_name,
            "key"    => $key,
            "type"   => $type,
            "label"  => $label,
            "status" => $status,
        );
    }
    
    foreach($original_services as $key => $service)
        if( ! isset($services[$key]) )
            $services[$key] = $service;
}

$ul_id = "sorder_" . uniqid();
?>

<div class="mobctrl_settings_services_order">
    <textarea name="names[<?= $params["name"] ?>]" class="expandible_textarea" style="display: none;"><?= $params["value"] ?></textarea>
    <ul id="<?= $ul_id ?>" class="sortable">
        <? foreach($services as $data): ?>
            <li data-module="<?= $data->module ?>" data-key="<?= $data->key ?>" data-type="<?= $data->type ?>"
                class="framed_content">
                <div class="mobctrl_items_container">
                    <span class="mobctrl_item handle fa fa-arrows-v fa-fw"></span>
                    <span class="mobctrl_item type framed_content inlined state_default"><?= $data->type ?></span>
                    <input class="mobctrl_item" type="text" data-field="label"
                           placeholder="<?= $this_module->language->set_label ?>"
                           onblur="reserialize_mobctrl_services( this )"
                           value="<?= htmlspecialchars($data->label) ?>">
                    <?
                    $value     = $data->status;
                    $style_on  = $value == "enabled" ? "" : "display: none;";
                    $style_off = $value != "enabled" ? "" : "display: none;";
                    echo "
                        <span class='mobctrl_item fa-pseudo-switch' data-value-on='enabled' data-value-off='disabled'
                              onclick='toggle_fa_pseudo_switch(this); reserialize_mobctrl_services( this );'>
                            <input type='hidden' data-field='state' value='$value'>
                            <span class='toggler toggle-on  fa fa-toggle-on'  style='$style_on'></span>
                            <span class='toggler toggle-off fa fa-toggle-off' style='$style_off'></span>
                        </span>
                    ";
                    ?>
                </div>
            </li>
        <? endforeach; ?>
    </ul>
</div>
