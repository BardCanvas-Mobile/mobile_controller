<?php
/**
 * Pre-rendering overrides
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var config   $config
 * @var module   $this_module
 * @var template $template;
 */

use hng2_base\config;
use hng2_base\module;
use hng2_base\template;

if( $_REQUEST["bcm_minimalistic_mode_enforced"] != "true" ) return;

$template->clear_right_sidebar_items();
$template->clear_left_sidebar_groups();
$template->append("additional_body_attributes", " data-bcm-minimalistic-mode='true'");
