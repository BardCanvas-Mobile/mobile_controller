<?php
/**
 * Authentication page and callback functionality
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

<script type="text/javascript">
    
    function bcm_facebook_login()
    {
        var incoming_token  = $('#incoming_token').text();
        var incoming_device = $('#incoming_device').text();
        
        $.blockUI(blockUI_medium_params);
        FB.login(function()
        {
            FB.api('/me', function(response)
            {
                if( response.error )
                {
                    alert( response.error.message );
                    $.unblockUI();
                    
                    return;
                }
                
                var user = response;
                FB.getLoginStatus(function(response)
                {
                    if (response.status !== 'connected')
                    {
                        alert('Facebook account is not connected. Aborting login.');
                        $.unblockUI();
                        
                        return;
                    }
                    
                    var accessToken = response.authResponse.accessToken;
                    
                    var url    = $_FULL_ROOT_PATH + '/single_signon/scripts/facebook.php';
                    var params = {
                        method:  'login',
                        id:      user.id,
                        token:   accessToken,
                        wasuuup: wasuuup()
                    };
                    
                    $.post(url, params, function(response)
                    {
                        if( response !== 'OK' )
                        {
                            $.unblockUI();
                            alert( response );
                            
                            return;
                        }
                        
                        location.href = sprintf(
                            '%s?mode=confirm&token=%s&device=%s&wasuuup=%s',
                            $_PHP_SELF,
                            incoming_token,
                            incoming_device,
                            wasuuup()
                        );
                    });
                });
            });
        }, {scope: 'email'});
    }
</script>

<div id="incoming_token"  style="display: none"><?= $incoming_token ?></div>
<div id="incoming_device" style="display: none"><?= $incoming_device ?></div>

<h1><?= $current_module->language->facebook_authenticator->authentication_page->title ?></h1>

<div>
    <?= replace_escaped_objects(
        $current_module->language->facebook_authenticator->authentication_page->info,
        array('{$root_path}' => $config->full_root_path)
    ) ?>
</div>

<? if( $account->_exists): ?>
    <p class="aligncenter">
        <span class="framed_content state_highlight">
            <i class="fa fa-info-circle"></i>
            <?= replace_escaped_objects(
                $current_module->language->facebook_authenticator->authentication_page->logged_in_as,
                array('{$display_name}' => $account->get_processed_display_name())
            ) ?>
        </span>
    </p>
<? endif; ?>

<p class="aligncenter">
    <span class="framed_content pseudo_link"
          style="color: white; background-color: #3B5998; border: none;"
         onclick="bcm_facebook_login()"><i class="fa fa-facebook-square"></i>
        <?= $current_module->language->facebook_authenticator->authentication_page->continue_with_facebook ?></span>
</p>

<div>
    <?= replace_escaped_objects(
        $current_module->language->facebook_authenticator->authentication_page->post_info,
        array('{$root_path}' => $config->full_root_path)
    ) ?>
</div>
