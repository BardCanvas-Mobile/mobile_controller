<?php
/**
 * Media processor: hooks to get_processed_content method in posts and comments records
 * to recompress images and videos as coming in $_REQUEST["media_processor_args"]:
 * "max_image_width:int,max_jpeg_quality:int,max_video_width:int,max_video_bitrate:string,convert_gifs_to_videos:bool"
 * Note: it runs at the end of the procedure, so any media item is already processed.
 *
 * @package    BardCanvas
 * @subpackage mobile_controller
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 */

use hng2_media\media_repository;
use hng2_modules\mobile_controller\media_processor_args;
use hng2_modules\mobile_controller\toolbox;

if( empty($_REQUEST["media_processor_args"]) ) return;
if( empty($_REQUEST["bcm_platform"]) ) return;

$contents = $config->globals["processing_contents"];
if( empty($contents) ) return;

include_once ROOTPATH . "/lib/phpQuery-onefile.php";

$document = phpQuery::newDocument($contents);
$document->find('img[data-media-type],video')->each(function($element)
{
    global $settings, $config;
    
    $element  = pq($element);
    $type     = $element->attr("data-media-type");
    $id_media = $element->attr("data-id-media");
    if( empty($id_media) ) return;
    
    $repository = $config->globals["modules:mobile_controller.media_repository"];
    if( empty($repository) ) {
        $repository = new media_repository();
        $config->globals["modules:mobile_controller.media_repository"] = $repository;
    }
    
    $toolbox = $config->globals["modules:mobile_controller.toolbox"];
    if( empty($toolbox) ) {
        $toolbox = new toolbox();
        $config->globals["modules:mobile_controller.toolbox"] = $toolbox;
    }
    
    $item = $repository->get($id_media);
    if( is_null($item) ) return;
    
    if( $type == "image" && trim(strtolower(end(explode(".", $item->path)))) == "gif" ) return;
    if( $type == "video" && $settings->get("engine.ffmpeg_path") == "" ) return;
    
    $args = new media_processor_args();
    # $element->attr("data-bcm-mpargs", $args->get_as_query_string($type));
    
    $is_processable = $args->is_processable($type);
    # $element->attr("data-bcm-processable", $is_processable ? "true" : "false");
    if( ! $is_processable ) return;
    
    $res = $toolbox->resample_media($item, $args);
    if( ! is_null($res) )
    {
        if( $item->type == "image")
        {
            $element->attr("src", "/mediaserver/" . dirname($item->path) . "/" . $res);
        }
        else
        {
            $config->globals["modules:mobile_controller.current_video_res"]  = $res;
            $config->globals["modules:mobile_controller.current_video_item"] = $item;
            $element->find("source")->each(function($source)
            {
                global $config;
                
                $res    = $config->globals["modules:mobile_controller.current_video_res"];
                $item   = $config->globals["modules:mobile_controller.current_video_item"];
                $source = pq($source);
                
                $source->attr("src", $config->full_root_url . "/mediaserver/" . dirname($item->path) . "/" . $res);
            });
        }
        
        $element->attr("data-bcm-processed", "true");
    }
});

$config->globals["processing_contents"] = $document->html();
