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
?>

<!-- Mobile controller additions -->

<script type="text/javascript">
    if( typeof GLOBAL_AJAX_ADDED_PARAMS === 'undefined' ) GLOBAL_AJAX_ADDED_PARAMS = {};
    GLOBAL_AJAX_ADDED_PARAMS.bcm_platform                   = '<?= $_GET["bcm_platform"] ?>';
    GLOBAL_AJAX_ADDED_PARAMS.bcm_access_token               = '<?= $_GET["bcm_access_token"] ?>';
    GLOBAL_AJAX_ADDED_PARAMS.bcm_force_session              = '<?= $_GET["bcm_force_session"] ?>';
    GLOBAL_AJAX_ADDED_PARAMS.bcm_output_type                = 'JSON';
    GLOBAL_AJAX_ADDED_PARAMS.bcm_minimalistic_mode_enforced = '<?= $_GET["bcm_minimalistic_mode_enforced"] ?>';
</script>

<? if( $config->globals["using_bcmobile"] ): ?>
    <script type="text/javascript">
        $_NOTIFICATIONS_SYSTEM_DISABLED = true;
    </script>
<? endif; ?>

<? if( $_REQUEST["bcm_minimalistic_mode_enforced"] != "true" ) return; ?>

<!-- Mobile controller global styles and stuff -->

<style type="text/css">
    
    #header, #footer, #left_sidebar, #right_sidebar, #quick_post_floating_trigger { display: none !important; }
    
</style>

<? if( $_REQUEST["bcm_minimalistic_sandboxing_disabled"] == "true" ) return; ?>

<!-- Mobile controller sandboxing -->

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
