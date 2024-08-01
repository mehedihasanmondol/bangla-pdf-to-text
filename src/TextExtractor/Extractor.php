<?php
namespace PdfToBanglaText\TextExtractor;
class Extractor{
    public $PDFPath = "";
    public $publicUploadFolder = "";
    public $languages = array('eng','ben');
    public $targetDir = "";
    public $pdfAsTmp = false;

    function save_pdf_tmp_as_pdf(){
        $new_target = $this->targetDir."/".uniqid().".pdf";
        move_uploaded_file($this->PDFPath, $new_target);
        $this->PDFPath = $new_target;
    }

    function ensure_tmp_director(){
        // Ensure the uploads directory exists
        if (!file_exists($this->targetDir)) {
            mkdir($this->targetDir, 0777, true);
        }
        
    }

    function extract_text_from_image($imagePath) {

        $outputFile = tempnam($this->targetDir, 'ocr_');
        $command = "tesseract $imagePath $outputFile -l ".join("+",$this->languages);
        shell_exec($command);
        $content = file_get_contents("$outputFile.txt");
        unlink("$outputFile.txt");
        unlink("$outputFile");
        return $content;
    }

    function set_pdf_path($path){
        $this->PDFPath = $path;
    }
    
    function set_public_upload_folder($path){
        $this->publicUploadFolder = $path;
    }
    
    function set_pdf_as_tmp(){
        $this->pdfAsTmp = true;
    }

    function configuration(){
        $this->targetDir = $this->publicUploadFolder."/tmp";
        $this->ensure_tmp_director();
        if($this->pdfAsTmp){
            $this->save_pdf_tmp_as_pdf();
        }
    }

    function execute(){
        $this->configuration();
        
        $pdf = new \Drenso\PdfToImage\Pdf($this->PDFPath);
        $txt = "";
        for($i=1; $i <= $pdf->getNumberOfPages(); $i++){
            $image_store_path = $this->targetDir."/".uniqid().$i.".png";
            $pdf->saveImage($image_store_path);

            $txt .= $this->extract_text_from_image($image_store_path);
            unlink($image_store_path);
        }

        if($this->pdfAsTmp){
            unlink($this->PDFPath);
        }
        return $txt;
    }
}