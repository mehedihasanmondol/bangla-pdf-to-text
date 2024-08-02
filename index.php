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
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    $fileType = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    // Check if file is a PDF
    if ($fileType === 'pdf') {
        $extractor = new PdfToBanglaText\TextExtractor\Extractor();
        $extractor->set_pdf_path($_FILES["file"]["tmp_name"]);
        $extractor->set_public_upload_folder(__DIR__."/uploads");
        $extractor->set_pdf_as_tmp();
        $txt = $extractor->execute();
        echo "<pre>";
        print_r($txt);
        echo "</pre>";

    } else {
        echo "File is not a PDF.";
    }
} 

?>

</body>
</html>
