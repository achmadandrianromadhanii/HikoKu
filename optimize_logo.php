<?php
$sourceFile = 'public/images/logo.png';
$targetFile = 'public/images/logo.webp';

if (!file_exists($sourceFile)) {
    die("Source file not found.");
}

list($width, $height, $type) = getimagesize($sourceFile);
$image = imagecreatefrompng($sourceFile);
if (!$image) die("Failed to read PNG.");

imagealphablending($image, false);
imagesavealpha($image, true);

$newWidth = 200;
$newHeight = ($height / $width) * $newWidth;
$newImage = imagecreatetruecolor($newWidth, $newHeight);

imagealphablending($newImage, false);
imagesavealpha($newImage, true);
$transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);

imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
imagewebp($newImage, $targetFile, 80);

imagedestroy($image);
imagedestroy($newImage);

echo "Successfully created logo.webp. Original size: " . filesize($sourceFile) . " bytes. New size: " . filesize($targetFile) . " bytes.\n";
?>
