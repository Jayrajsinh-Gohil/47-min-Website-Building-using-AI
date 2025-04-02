<?php
// Set the content type to image/png
header('Content-Type: image/png');

// Get parameters from URL
$width = isset($_GET['width']) ? (int)$_GET['width'] : 300;
$height = isset($_GET['height']) ? (int)$_GET['height'] : 250;
$text = isset($_GET['text']) ? $_GET['text'] : 'Placeholder';
$bg_color = isset($_GET['bg']) ? $_GET['bg'] : '0071e3'; // Default to primary color
$text_color = isset($_GET['text_color']) ? $_GET['text_color'] : 'FFFFFF';

// Function to convert hex to RGB
function hex2rgb($hex) {
    // Remove # if present
    $hex = str_replace('#', '', $hex);
    
    // Handle both 3-digit and 6-digit formats
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    return array($r, $g, $b);
}

// Convert hex colors to RGB
list($bg_r, $bg_g, $bg_b) = hex2rgb($bg_color);
list($text_r, $text_g, $text_b) = hex2rgb($text_color);

// Create the image
$image = imagecreatetruecolor($width, $height);

// Allocate colors
$bg = imagecolorallocate($image, $bg_r, $bg_g, $bg_b);
$text_color = imagecolorallocate($image, $text_r, $text_g, $text_b);

// Fill the background
imagefill($image, 0, 0, $bg);

// Use the built-in font
$font = 5; // Largest built-in font
$text_width = strlen($text) * imagefontwidth($font);
$text_height = imagefontheight($font);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

// Add text to the image
imagestring($image, $font, $x, $y, $text, $text_color);

// Output the image
imagepng($image);

// Free memory
imagedestroy($image);
?> 