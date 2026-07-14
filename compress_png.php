<?php
$sourceFile = 'public/images/logo.png';
$targetFile = 'public/images/logo-optimized.png';

if (!file_exists($sourceFile)) {
    die("Source file not found.");
}

list($width, $height, $type) = getimagesize($sourceFile);
$image = imagecreatefrompng($sourceFile);

$newWidth = 150;
$newHeight = ($height / $width) * $newWidth;

$newImage = imagecreatetruecolor($newWidth, $newHeight);
imagealphablending($newImage, false);
imagesavealpha($newImage, true);
$transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);

imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

imagepng($newImage, $targetFile, 9);
imagedestroy($image);
imagedestroy($newImage);

rename($targetFile, $sourceFile);

echo "Successfully compressed logo.png to " . filesize($sourceFile) . " bytes.\n";
?>
