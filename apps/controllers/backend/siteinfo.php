<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Siteinfo extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $data['title'] = "Site Info";
        $this->tabel->_table = "static";
        $data['siteinfo'] = $this->tabel->find_where(array("page" => "page"));
        $data['page'] = "backend/siteinfo";
        $this->load->view('backend/page', $data);
    }

    public function save() {

        $data = $this->input->post('data');
        $dataSave = array();

        $dataSave['date_update'] = date('Y-m-d H:i:s');

        $this->tabel->_table = "static";

        foreach ($data as $key => $value) {
            if ( get_magic_quotes_gpc() )
                $dataSave['value'] = trim(htmlspecialchars( stripslashes((string)$value) ));
            else
                $dataSave['value'] = trim(htmlspecialchars( (string)$value ));

            $this->tabel->update_where(array("page" => "page", "key" => $key), $dataSave);
        }

        $msgBack = array();
        
        $msgBack['IsError'] = false;
        $msgBack['Msg'] = "Data Site Info is edited succesfully.";
   
        echo json_encode($msgBack);
    }

    public function deletepicture() {

        $value= urldecode($this->input->Post('value'));
        $targetDir = APPCONTENT . 'banner/';
        if (@unlink($targetDir . $value)){
            $msgBack['isSukses'] = true;
            $msgBack['msg'] = "Image $value is deleted successfully.";
        }
        else{
            $msgBack['isSukses'] = false;
            $msgBack['msg'] = "Image $value cannot be deleted.";
        }
            
        echo json_encode($msgBack);
    }

    public function upload($func = "", $param = null){

        // HTTP headers for no cache etc
        //header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        // Settings
        //$targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
        $targetDir = APPCONTENT . 'banner';

        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds

        // 5 minutes execution time
        @set_time_limit(5 * 60);

        // Uncomment this one to fake upload time
        // usleep(5000);

        // Get parameters
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

        // Clean the fileName for security reasons
        $fileName = preg_replace('/[^\w\._]-()+/', '_', $fileName);
        
        $chkFileExt = explode(".",$fileName);
        $chkFileExt = $chkFileExt[count($chkFileExt)-1];

        // Make sure the fileName is unique but only if chunking is disabled
        if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
            $ext = strrpos($fileName, '.');
            $fileName_a = substr($fileName, 0, $ext);
            $fileName_b = substr($fileName, $ext);

            $count = 1;
            while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
                $count++;

            $fileName = $fileName_a . '_' . $count . $fileName_b;
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        // Create target dir
        if (!file_exists($targetDir))
            @mkdir($targetDir);

        // Remove old temp files    
        if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
                    @unlink($tmpfilePath);
                }
            }

            closedir($dir);
        } else
            die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            

        // Look for the content type header
        if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
            $contentType = $_SERVER["HTTP_CONTENT_TYPE"];

        if (isset($_SERVER["CONTENT_TYPE"]))
            $contentType = $_SERVER["CONTENT_TYPE"];

        // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                // Open temp file
                $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = fopen($_FILES['file']['tmp_name'], "rb");

                    if ($in) {
                        while ($buff = fread($in, 4096))
                            fwrite($out, $buff);
                    } else
                        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                    fclose($in);
                    fclose($out);
                    @unlink($_FILES['file']['tmp_name']);
                } else
                    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            } else
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
        } else {
            // Open temp file
            $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = fopen("php://input", "rb");

                if ($in) {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                } else
                    die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

                fclose($in);
                fclose($out);
            } else
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }

        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off 
            rename("{$filePath}.part", $filePath);
        }
        
        // Return JSON-RPC response
        die('{"jsonrpc" : "2.0", "result" : null, "id" : "id", "filename" : "'. $fileName .'"}');
    }

}

/* End of file home.php */
/* Location: ./apps/controllers/home.php */