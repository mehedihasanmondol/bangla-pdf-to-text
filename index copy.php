<?php

require "vendor/autoload.php";
use Spatie\PdfToImage\Pdf;

// $pdf = new Pdf(__DIR__."/nee_1.pdf");

// $pdf->save(__DIR__."/exported/nee_1.jpg");

$pdf = new Drenso\PdfToImage\Pdf(__DIR__."/nee_1.pdf");
$pdf->saveImage(__DIR__."/exported/nee_1.jpg");


die();
// Check if Imagick is installed
if (!extension_loaded('imagick')) {
    die('Imagick not installed');
}

// Check if Tesseract is installed
$output = shell_exec('tesseract -v');
if (strpos($output, 'tesseract') === false) {
    die('Tesseract not installed or not found in PATH');
}

function convertPdfToImages($pdfFilePath, $outputDir) {
    $imagick = new \Imagick();
    // Set the resolution (density)
    $imagick->setResolution(200, 200); // Set vertical and horizontal resolution to 200 DPI


    $imagick->readImage($pdfFilePath);
    // Set image format to jpg
    $imagick->setImageFormat('jpg');
 
    // Set compression quality (optional)
    $imagick->setImageCompressionQuality(100); // 100 for maximum quality
    // Enable antialiasing
    $imagick->setOption('antialias', 'true');

    foreach ($imagick as $i => $image) {
        // Flatten the image to remove alpha channel
        $image = $image->flattenImages();
         // Set antialiasing for better text rendering
        $image->setOption('pdf:use-cropbox', 'true');
        
        // Set background color to white (optional)
        $image->setImageBackgroundColor('white');
        $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        // Resize the image to 2338x1652 pixels
        $image->resizeImage(2338, 1652, Imagick::FILTER_LANCZOS, 1);

        // Set the image depth to 24
        $image->setImageDepth(24);

        // Set vertical and horizontal resolution to 200 DPI
        $image->setImageResolution(200, 200);


        $imagePath = $outputDir . '/page-' . $i . '.jpg';
        $image->writeImage($imagePath);
    }
}

function extractTextFromImage($imagePath, $languages = 'eng') {
    $outputFile = tempnam(__DIR__."/tmp", 'ocr_');
    $command = "tesseract $imagePath $outputFile -l $languages";
    echo $command;
    shell_exec($command);
    return file_get_contents("$outputFile.txt");
}

function pdfToText($pdfFilePath, $outputDir, $languages = 'eng') {
    if (!file_exists($pdfFilePath)) {
        die('PDF file not found');
    }

    if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
        die('Failed to create output directory');
    }

    convertPdfToImages($pdfFilePath, $outputDir);

    $text = '';
    foreach (glob("$outputDir/page-*.jpg") as $imagePath) {
        $text .= extractTextFromImage($imagePath, $languages) . "\n";
    }

    // Clean up generated images
    // array_map('unlink', glob("$outputDir/page-*.png"));

    return $text;
}

echo '<pre>';
// Usage example
$pdfFilePath = __DIR__.'/nee_1.pdf';
$outputDir = __DIR__.'/exported';
$languages = 'ben+eng'; // Set languages to English and Bangla
$text = pdfToText($pdfFilePath, $outputDir, $languages);
echo $text;

?>
