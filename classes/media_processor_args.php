<?php
namespace hng2_modules\mobile_controller;

class media_processor_args
{
    public $max_image_width  = 0;
    public $max_jpeg_quality = 0;
    
    public $max_video_width   = 0;
    public $max_video_bitrate = "";
    
    public function __construct()
    {
        if( empty($_REQUEST["media_processor_args"]) ) return;
        
        parse_str(str_replace(array(",", ":"), array("&", "="), trim($_REQUEST["media_processor_args"])), $args);
        foreach($args as $key => $val) $this->{$key} = $val;
    }
    
    /**
     * @param string $item_type image|video
     *
     * @return bool
     */
    public function is_processable($item_type)
    {
        if( $item_type == "image" &&
            $this->max_image_width == 0 &&
            ($this->max_jpeg_quality == 0 || $this->max_jpeg_quality == 100) )
            return false;
        
        if( $item_type == "video" && $this->max_video_width == 0 && $this->max_video_bitrate == "" )
            return false;
        
        return true;
    }
    
    public function get_as_query_string($media_type = "")
    {
        if( $media_type == "image" )
            return sprintf(
                "max_image_width=%s&max_jpeg_quality=%s",
                $this->max_image_width,
                $this->max_jpeg_quality
                
            );
        
        if( $media_type == "video" )
            return sprintf(
                "max_video_width=%s&max_video_bitrate=%s",
                $this->max_video_width,
                $this->max_video_bitrate
    
            );
         
        return sprintf(
            "max_image_width=%s&max_jpeg_quality=%s&max_video_width=%s&max_video_bitrate=%s",
            $this->max_image_width,
            $this->max_jpeg_quality,
            $this->max_video_width,
            $this->max_video_bitrate
    
        );
    }
}
