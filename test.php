<?php
require "vendor/autoload.php";

$pdf = new Drenso\PdfToImage\Pdf(__DIR__."/1.pdf");


function extractTextFromImage($imagePath, $languages = 'ben+eng') {
    $outputFile = tempnam(__DIR__."/tmp", 'ocr_');
    $command = "tesseract $imagePath $outputFile -l $languages";
    shell_exec($command);
    return file_get_contents("$outputFile.txt");
}

$txt = "";
for($i=1; $i <= $pdf->getNumberOfPages(); $i++){
    $image_store_path = __DIR__."/exported/nee_".$i.".png";
    $pdf->saveImage($image_store_path);

    $txt .= extractTextFromImage($image_store_path);
}

echo "<pre>";
echo $txt;