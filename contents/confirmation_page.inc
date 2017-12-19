<?php
/**
 * Authentication confirmation page
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
?>

<h1><?= $current_module->language->facebook_authenticator->authenticated->title ?></h1>

<div>
    <?= replace_escaped_objects(
        $current_module->language->facebook_authenticator->authenticated->info,
        array('{$root_path}' => $config->full_root_path, '{$display_name}' => $account->get_processed_display_name())
    ) ?>
</div>
