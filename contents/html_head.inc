<?php
/**
 * Styles and scripts for the module
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
?>

<!-- Mobile controller global styles and stuff -->

<style type="text/css">
    
    #header, #footer, #left_sidebar, #right_sidebar { display: none !important; }
    
</style>

<script type="text/javascript">
    $(document).ready(function()
    {
        var $body = $('body');
        
        $body.find('iframe').prop('sandbox', true);
        $body.find('a, .pseudo_link').each(function()
        {
            var $this = $(this);
            
            $this.prop('disabled', true);
            $this.addClass('disabled');
            $this.click(function() { return false; })
        });
    });
</script>
