<?php
/**
 * Bootstrap extender
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * Trailing vars:
 * @var module[] $modules
 * @var module   $current_module (settings)
 * @var module   $this_module    (self)
 */

use hng2_base\module;

/** @var \SimpleXMLElement $module_language */
$module_language = $this_module->language;

foreach( $modules as $m )
{
    if( ! $m->enabled ) continue;
    
    $element = $m->language->xpath("//exported_settings_data[@for=\"{$this_module->name}\"]");
    if( empty($element) ) continue;
    
    $specs = $m->language->xpath("//exported_settings_data[@for=\"{$this_module->name}\"]/specs");
    if( empty($module_language->settings_data) ) $module_language->addChild("settings_data");
    
    /** @noinspection PhpParamsInspection */
    $tgt = dom_import_simplexml($module_language->settings_data);
    foreach($specs as $spec)
    {
        $src = dom_import_simplexml($spec);
        $tgt->appendChild($tgt->ownerDocument->importNode($src, true));
    }
}

