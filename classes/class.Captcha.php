<?php
/*
* BtiTracker v1.5.0 is a php tracker system for BitTorrent, easy to setup and configure.
* This tracker is a frontend for DeHackEd's tracker, aka phpBTTracker (now heavely modified). 
* Updated and Maintained by Yupy.
* Copyright (C) 2004-2014 Btiteam.org
*/

class ocr_captcha
{
    var $key; // ultra private static text
    var $long; // size of text
    var $lx; // width of picture
    var $ly; // height of picture
    var $nb_noise; // nb of background noisy characters
    var $filename; // file of captcha picture stored on disk
    var $imagetype = "png"; // can also be "png";
    var $public_key; // public key
    var $font_file = "./include/adlibn.ttf";
    function ocr_captcha($long = 6, $lx = 120, $ly = 30, $nb_noise = 25)
    {
        $this->key        = md5("A nicely little text to stay private and use for generate private key");
        $this->long       = $long;
        $this->lx         = $lx;
        $this->ly         = $ly;
        $this->nb_noise   = $nb_noise;
        $this->public_key = substr(md5(uniqid(mt_rand(), true)), 0, $this->long); // generate public key with entropy
    }
    
    function get_filename($public = "")
    {
        global $TORRENTSDIR;
        if ($public == "")
            $public = $this->public_key;
        return $TORRENTSDIR . "/" . $public . "." . $this->imagetype;
    }
    
    // generate the private text coming from the public text, using $this->key (not to be public!!), all you have to do is here to change the algorithm
    function generate_private($public = "")
    {
        if ($public == "")
            $public = $this->public_key;
        return substr(md5($this->key . $public), 16 - $this->long / 2, $this->long);
    }
    
    // check if the public text is link to the private text
    function check_captcha($public, $private)
    {
        // when check, destroy picture on disk
        if (file_exists($this->get_filename($public)))
            unlink($this->get_filename($public));
        return (strtolower($private) == strtolower($this->generate_private($public)));
    }
    
    // display a captcha picture with private text and return the public text
    function make_captcha($noise = true)
    {
        $private_key = $this->generate_private();
        $image       = imagecreatetruecolor($this->lx, $this->ly);
        $back        = ImageColorAllocate($image, intval(mt_rand(224, 255)), intval(mt_rand(224, 255)), intval(mt_rand(224, 255)));
        ImageFilledRectangle($image, 0, 0, $this->lx, $this->ly, $back);
        if ($noise) { // mt_rand characters in background with mt_random position, angle, color
            for ($i = 0; $i < $this->nb_noise; $i++) {
                $size  = intval(mt_rand(6, 14));
                $angle = intval(mt_rand(0, 360));
                $x     = intval(mt_rand(10, $this->lx - 10));
                $y     = intval(mt_rand(0, $this->ly - 5));
                $color = imagecolorallocate($image, intval(mt_rand(160, 224)), intval(mt_rand(160, 224)), intval(mt_rand(160, 224)));
                $text  = chr(intval(mt_rand(45, 250)));
                ImageTTFText($image, $size, $angle, $x, $y, $color, $this->font_file, $text);
            }
        } else { // mt_random grid color
            for ($i = 0; $i < $this->lx; $i += 10) {
                $color = imagecolorallocate($image, intval(mt_rand(160, 224)), intval(mt_rand(160, 224)), intval(mt_rand(160, 224)));
                imageline($image, $i, 0, $i, $this->ly, $color);
            }
            for ($i = 0; $i < $this->ly; $i += 10) {
                $color = imagecolorallocate($image, intval(mt_rand(160, 224)), intval(mt_rand(160, 224)), intval(mt_rand(160, 224)));
                imageline($image, 0, $i, $this->lx, $i, $color);
            }
        }
        // private text to read
        for ($i = 0, $x = 5; $i < $this->long; $i++) {
            $r      = intval(mt_rand(0, 128));
            $g      = intval(mt_rand(0, 128));
            $b      = intval(mt_rand(0, 128));
            $color  = ImageColorAllocate($image, $r, $g, $b);
            $shadow = ImageColorAllocate($image, $r + 128, $g + 128, $b + 128);
            $size   = intval(mt_rand(12, 17));
            $angle  = intval(mt_rand(-30, 30));
            $text   = strtoupper(substr($private_key, $i, 1));
            ImageTTFText($image, $size, $angle, $x + 2, 26, $shadow, $this->font_file, $text);
            ImageTTFText($image, $size, $angle, $x, 24, $color, $this->font_file, $text);
            $x += $size + 2;
        }
        if ($this->imagetype == "jpg")
            imagejpeg($image, $this->get_filename(), 100);
        else
            imagepng($image, $this->get_filename());
        ImageDestroy($image);
    }
    
    function display_captcha($noise = true)
    {
        $this->make_captcha($noise);
        $res = "<input type='hidden' name='public_key' value='" . $this->public_key . "'>\n";
        $res .= "<img align='middle' src='" . $this->get_filename() . "' border='0'>\n";
        return $res;
    }
}

?>