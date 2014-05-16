<?php
/**
 * Created by PhpStorm.
 * User: gilzow
 * Date: 5/15/14
 * Time: 1:25 PM
 */

class MizzouPost
{
    public $ID              = '';
    public $author          = '';
    public $date            = '';
    public $date_gmt        = '';
    public $content         = '';
    public $content_raw     = '';
    public $title           = '';
    public $title_raw       = '';
    public $excerpt         = '';
    public $status          = '';
    public $comment_status  = '';
    public $ping_status     = '';
    public $password        = '';
    public $name            = '';
    public $to_ping         = '';
    public $pinged          = '';
    public $modified        = '';
    public $modified_gmt    = '';
    public $content_filtered= '';
    public $parent          = '';
    public $guid            = '';
    public $permalink       = '';
    public $menu_order      = '';
    public $post_type       = '';
    public $mime_type       = '';
    public $comment_count   = '';
    public $filter          = '';
    public $meta_data       = '';

    protected $strPostPrefix= 'post_';
    protected $aryPropertyExclude = array('post_type');

    public function __construct(WP_Post $objPost)
    {
        $this->_set_members($objPost);
    }

    private function _set_members(WP_Post $objPost)
    {
        $aryPostMembers = get_object_vars($objPost);
        $intPrefixLen = strlen($this->strPostPrefix);
        foreach($aryPostMembers as $strProperty => $strPropertyVal){
            if(0 === strpos($strProperty,$this->strPostPrefix) && !in_array($strProperty,$this->aryPropertyExclude)){
                $strProperty = substr($strProperty,$intPrefixLen);
            }

            if(isset($this->{$strProperty})){
                $this->{$strProperty} = $strPropertyVal;
            }
        }

        $this->_setPermalink();
        $this->_setContent();
        $this->_setTitle();
    }

    private function _setPermalink()
    {
        $this->permalink = get_permalink($this->ID);
    }

    private function _setContent()
    {
        $this->content_raw = $this->content;
        $this->content = apply_filters('the_content',$this->content_raw);
    }

    private function _setTitle()
    {
        $this->title_raw = $this->title;
        $this->title = apply_filters('the_title',$this->title_raw);
    }
} 