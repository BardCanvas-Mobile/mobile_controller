<?php
/**
 * Settings preinits
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */
?>

<!-- Mobile controller preinits -->

<style type="text/css">
    .mobctrl_settings_services_order ul { list-style-type: none; margin: 0; padding: 0; }
    .mobctrl_settings_services_order li { margin: 3px 0; padding: 5px; }
    
    .mobctrl_items_container                        { display: flex; }
    .mobctrl_items_container .mobctrl_item          { margin: 2px; }
    .mobctrl_items_container .mobctrl_item.type     { width: 30%; }
</style>

<script type="text/javascript">
    
    function reserialize_mobctrl_services( trigger )
    {
        var $trigger   = $(trigger);
        var $container = $trigger.closest('.mobctrl_settings_services_order');
        var $textarea  = $container.find('textarea');
        var $items     = $container.find('ul li');
        
        var contents = '';
        $items.each(function()
        {
            var $this  = $(this);
            var module = $this.attr('data-module');
            var key    = $this.attr('data-key');
            var type   = $this.attr('data-type');
            var label  = $this.find('input[data-field="label"]').val().trim();
            var state  = $this.find('input[data-field="state"]').val();
            
            contents = contents + sprintf('%s | %s | %s | %s | %s\n', module, key, type, label, state);
        });
        contents = $.trim(contents);
        $textarea.val(contents);
    }
    
    $(document).ready(function()
    {
        $('.mobctrl_settings_services_order .sortable').sortable({
            handle: '.handle',
            update: function(e, ui) {reserialize_mobctrl_services(ui.item);}
        });
    });
</script>
