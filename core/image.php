<?php
defined('CMSPATH') or die; // prevent unauthorized access

class Image {
	public $id;
    public $filename;
    public $width;
    public $height;
    public $title;
    public $alt;
    public $mimetype;
    // modified also available, but almost certainly never needed front-end
    
    public function __construct($id) {
        if (!is_numeric($id)) {
            CMS::Instance()->show_error('Cannot create image object from non-numerical id');
        }
        else {
            $this->id = $id;
            $stmt = CMS::Instance()->pdo->prepare('select * from media where id=?');
            $stmt->execute(array($this->id)); 
            $db_image = $stmt->fetch();
            $this->filename = $db_image->filename;
            $this->width = $db_image->width;
            $this->height = $db_image->height;
            $this->title = $db_image->title;
            $this->alt = $db_image->alt;
            $this->mimetype = $db_image->mimetype;
        }       
    }
    
    public function render($size="original", $class="") {
        // size should be original, web, or thumb 
        echo "<img loading='lazy' class='{$class}' src='" . Config::$uripath . "/image/" . $this->id . "/" . $size . "' alt='{$this->alt}' title='{$this->title}'/>";
    }
}