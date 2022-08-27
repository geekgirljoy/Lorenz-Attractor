<?php

// Calculate the Lorenz attractor and plot it to a PNG image with GD and a video using ffmpeg

// Lorenz attractor
function Lorenz($x, $y, $z, $rho, $sigma, $beta) {
    $dx = $sigma * ($y - $x);
    $dy = $x * ($rho - $z) - $y;
    $dz = $x * $y - $beta * $z;
    return array($dx, $dy, $dz);
}

// Calculate the next point in the Lorenz attractor
function NextPoint($x, $y, $z, $rho, $sigma, $beta, $dt) {
    list($dx, $dy, $dz) = Lorenz($x, $y, $z, $rho, $sigma, $beta);
    $x += $dx * $dt;
    $y += $dy * $dt;
    $z += $dz * $dt;
    return array($x, $y, $z);
}

// Calculate the Lorenz attractor
function Calculate($rho, $sigma, $beta, $dt, $n) {
    $x = 1.0;
    $y = 1.0;
    $z = 1.0;
    $points = array();
    for ($i = 0; $i < $n; $i++) {
        list($x, $y, $z) = NextPoint($x, $y, $z, $rho, $sigma, $beta, $dt);
        $points[] = array($x, $y, $z);
    }
    return $points;
}

// Plot the Lorenz attractor to a PNG image
function plot($points, $width, $height, $filename) {
    // Create the image
    $im = imagecreatetruecolor($width, $height);
    // Allocate colors
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);
    // Fill the background
    imagefill($im, 0, 0, $black);

    // Determine the range of the points for scaling
    $n = count($points);
    $min_x = $min_y = $min_z = 0;
    $max_x = $max_y = $max_z = 0;
    for ($i = 0; $i < $n; $i++) {
        list($x, $y, $z) = $points[$i];
        if ($x < $min_x) $min_x = $x;
        if ($x > $max_x) $max_x = $x;
        if ($y < $min_y) $min_y = $y;
        if ($y > $max_y) $max_y = $y;
        if ($z < $min_z) $min_z = $z;
        if ($z > $max_z) $max_z = $z;
    }
    $scale_x = $width / ($max_x - $min_x); // Scale factor for x
    $scale_y = $height / ($max_y - $min_y); // Scale factor for y
    $scale_z = $height / ($max_z - $min_z); // Scale factor for z

    // Plot the points
    for ($i = 0; $i < $n; $i++) {
        // Get the point
        list($x, $y, $z) = $points[$i];
        $x = ($x - $min_x) * $scale_x; // Scale x to the image width
        $y = ($y - $min_y) * $scale_y; // Scale y to the image height
        $z = ($z - $min_z) * $scale_z; // Scale z to the image height
        
        // Fit the point $x, $y, $z coordinates to the range 0-255 for the color
        $r = (int)($x * 255 / $width);  // red
        $g = (int)($y * 255 / $height); // green
        $b = (int)($z * 255 / $height); // blue

        // Use the RGB values to create a new color that represents the point
        $color = imagecolorallocate($im, $r, $g, $b);

        // Draw the point
        imagesetpixel($im, $x, $y, $color);
    }
    // Save the image as a PNG and free the memory
    imagepng($im, $filename);
    imagedestroy($im);
}


// Generate a series of png images for the points (if they are not already generated)
// in the Lorenz attractor and then create an mp4 video using ffmpeg
function CreateVideo($rho, $sigma, $beta, $dt, $n, $width, $height, $vid_filename, $points = null) {

    // Calculate the points if they are not passed in
    if ($points === null) {
        $points = Calculate($rho, $sigma, $beta, $dt, $n); // Calculate the points
    }

    $n = count($points); // number of points in the attractor sequence
    $i = 0; // frame number
    
    $digits_length = strlen((string)$n); // number of digits in the frame number
    $digits_format = "%0{$digits_length}d";// format the string for the frame number

    // draw the points with each image containing all previous points to create an animation of the attractor path
    foreach ($points as $point) {
        $i++;
        $img_filename = sprintf("lorenz_".$digits_format.".png", $i);
        plot(array_slice($points, 0, $i), $width, $height, $img_filename);
    }

    $cmd = "ffmpeg -r 270 -i lorenz_$digits_format.png -vcodec libx264 -crf 25 -pix_fmt yuv420p $vid_filename";
    
    system($cmd);
}

$image_width = 1024*2; // Width of the image in pixels = 2048
$image_height = 768*2; // Height of the image in pixels = 1536

$rho = 28.0;   // ρ (rho)
$sigma = 10.0; // σ (sigma)
$beta = 8.0/3.0; // β (beta) = ~2.6666666666667

// Calculate the Lorenz attractor series of points
$points = Calculate($rho, $sigma, $beta, 0.001, 100000); //  Calculate($rho, $sigma, $beta, 0.01, 100000);  

// Plot the whole Lorenz attractor series to a PNG image
plot($points, $image_width, $image_height, 'lorenz.png'); 

// Create a video of the Lorenz attractor - this takes a long time :-(
//CreateVideo($rho, $sigma, $beta,  0.001, 100000, $image_width, $image_height, 'lorenz.mp4');

?>
