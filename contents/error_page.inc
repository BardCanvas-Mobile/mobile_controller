<?php
/**
 * Error page
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

<h1><?= $template->get("error_page_title") ?></h1>

<div>
    <?= $template->get("error_page_content") ?>
</div>
