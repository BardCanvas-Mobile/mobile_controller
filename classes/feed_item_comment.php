<?php
namespace hng2_modules\mobile_controller;

use hng2_repository\abstract_record;

class feed_item_comment extends abstract_record
{
    public $id = 0;
    
    public $creation_date = "";
    
    public $content = "";
    
    public $excerpt = "";
    
    public $indent_level = 1;
    
    /**
     * @var string[]
     */
    public $extra_content_blocks = array();
    
    public $creation_ip = "";
    
    public $creation_location = "";
    
    public $author_id = 0;
    
    public $author_user_name = "";
    
    public $author_avatar = "";
    
    public $author_display_name = "";
    
    public $comment_reply_path = "";
    
    public $author_creation_date = "";
    
    public $author_level = 0;
    
    /**
     * @var action_trigger[]
     */
    public $action_triggers = array();
    
    public $has_actions = false;
    
    public function set_new_id() {}
}
