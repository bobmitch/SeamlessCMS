<?php
defined('CMSPATH') or die; // prevent unauthorized access


//$all_tags = Tag::get_all_tags();

$all_tags = Tag::get_all_tags_by_depth();
