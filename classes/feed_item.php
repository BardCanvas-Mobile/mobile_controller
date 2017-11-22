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
    
    public $featured_image_path      = "";
    public $featured_image_thumbnail = "";
    
    public $main_category_title   = "";
    public $parent_category_title = "";
    
    public $title   = "";
    public $excerpt = "";
    public $content = "";
    
    public $publishing_date   = "";
    public $comments_count    = 0;
    public $creation_ip       = "";
    public $creation_location = "";
    
    /**
     * @var bool
     */
    public $author_can_be_disabled = false;
    
    /**
     * @var bool
     */
    public $can_be_deleted = false;
    
    /**
     * @var bool
     */
    public $can_be_drafted = false;
    
    /**
     * @var bool
     */
    public $can_be_flagged_for_review = false;
    
    /**
     * @var feed_item_extra_content_block[]
     */
    public $extra_content_blocks = array();
    
    public function set_new_id() {}
}
