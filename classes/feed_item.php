<?php
namespace hng2_modules\mobile_controller;

use hng2_repository\abstract_record;

class feed_item extends abstract_record
{
    public $type = "";
    public $id   = 0;
    
    public $author_user_name     = "";
    public $author_level         = 0;
    public $author_avatar        = "";
    public $author_display_name  = "";
    public $author_creation_date = "";
    public $author_country_name  = "";
    
    public $featured_image_id              = 0;
    public $featured_image_path            = "";
    public $featured_image_thumbnail       = "";
    public $has_featured_image             = false;
    public $featured_image_not_in_contents = false;
    
    public $main_category_title   = "";
    public $parent_category_title = "";
    
    public $permalink = "";
    public $title     = "";
    public $excerpt   = "";
    public $content   = "";
    public $tags_list = array();
    
    public $publishing_date   = "";
    public $creation_ip       = "";
    public $creation_location = "";
    
    /**
     * @var action_trigger[]
     */
    public $index_action_triggers = array();
    
    public $has_index_actions = false;
    
    /**
     * @var action_trigger[]
     */
    public $item_action_triggers = array();
    
    public $has_item_actions = false;
    
    /**
     * @var content_block[]
     */
    public $excerpt_extra_blocks = array();
    
    /**
     * @var content_block[]
     */
    public $extra_content_blocks = array();
    
    public $comments_count = 0;
    
    /**
     * @var feed_item_comment[]
     */
    public $comments = array();
    
    public $allow_new_comments = false;
    
    public $add_comment_action = null;
    
    public $comments_limit_for_index = 10;
    
    public function set_new_id() {}
}
