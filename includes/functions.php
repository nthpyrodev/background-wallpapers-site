<?php

function convertToWebP($source, $mime_type) {
    $image = false;

    switch ($mime_type) {
        case 'image/png':
            $image = @imagecreatefrompng($source);
            break;
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($source);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($source);
            break;
    }

    if ($image === false) {
        return false;
    }

    if (!imageistruecolor($image)) {
        $tmp = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagecopy($tmp, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);
        $image = $tmp;
    }

    if ($mime_type === 'image/png') {
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    return $image;
}


function formatDate($date) {
    return date("M j, Y", strtotime($date));
}
