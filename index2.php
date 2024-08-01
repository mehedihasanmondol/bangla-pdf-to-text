<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload PDF for OCR</title>
</head>
<body>
    <h1>Upload PDF for OCR</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <label for="file">Choose a PDF:</label>
        <input type="file" name="file" id="file" accept="application/pdf" required>
        <button type="submit">Upload and Process</button>
    </form>

<?php
require_once 'pdf2text.php';
require_once 'pdf-img.php';
require 'vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

$apiSecret = '1C3jBWrRmizjCLgy'; // Replace with your ConvertAPI secret key

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["file"]["name"]);
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if file is a PDF
    if ($fileType === 'pdf') {
        // Ensure the uploads directory exists
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
            // Prepare the data for HTTP POST to ConvertAPI
            $postData = array(
                'File' => curl_file_create($targetFile, 'application/pdf', basename($targetFile)),
                'StoreFile' => 'true'
            );
            $id = uniqid(); // Generate a unique ID for the images
            $pdfImgResult = pdf2img($targetFile, $id);

            print_r($pdfImgResult);
            // Convert PDF to JPG using ConvertAPI (only 1st and 2nd pages)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://v2.convertapi.com/convert/pdf/to/jpg?Secret=$apiSecret");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'File' => new CURLFile($targetFile),
                'StoreFile' => 'true',
                'PageRange' => '1-2' // Convert only the first two pages
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                echo "cURL Error: $error";
            } else {
                $responseJson = json_decode($response, true);
                if (isset($responseJson['Files'])) {
                    $text = "";
                    $imagePaths = [];
                    foreach ($responseJson['Files'] as $file) {
                        $imageUrl = $file['Url'];
                        $imagePath = $targetDir . basename($imageUrl);
                        file_put_contents($imagePath, file_get_contents($imageUrl));
                        $imagePaths[] = $imagePath;

                        // Perform OCR on the image
                        $ocrText = (new TesseractOCR($imagePath))
                            ->lang('eng', 'ben') // English and Bangla
                            ->run();

                        $text .= $ocrText;
                    }

                    // Extract information from OCR text
                    $extracted_data = pdf2text($text);

                    // Display extracted data
                    echo "<h2>Extracted Information:</h2>";
                    echo "<pre>";
                    print_r($extracted_data);
                    echo "</pre>";

                    // Display images
                    echo "<h2>Extracted Images:</h2>";
                    foreach ($imagePaths as $path) {
                        echo "<img src='$path' alt='Extracted Image' style='max-width:100%; height:auto;'><br>";
                    }

                    // Delete uploaded file and images
                    unlink($targetFile);
                    foreach ($imagePaths as $path) {
                        unlink($path);
                    }
                } else {
                    echo "Error: Unable to convert PDF to images.";
                }
            }
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    } else {
        echo "File is not a PDF.";
    }
} else {
    echo "Invalid request.";
}
?>

</body>
</html>
