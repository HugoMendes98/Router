<?php

class Response {

    private $_baseUrl;
    private $_viewsPath = null;

	/**
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->_baseUrl;
	}
    /**
     * Response constructor.
     * @param string $baseUrl http url
     * @param string|null $viewsPath views folder
     */
    public function __construct(string $baseUrl, string $viewsPath = null) {
        $this->_baseUrl = $baseUrl;
        $this->_viewsPath = $viewsPath;
    }

    /**
     * Stop the execution of the script
     */
    private function stopExec() {
        exit();
    }

    /**
     * Render a view
     * @param string $viewPath path to the view
     * @param array $params sended to the view
     */
    public function render(string $viewPath, array $params = []) {

        /**
         * Load something ['js' or 'css']
         * @param string|string[] $files
         * @param string $type
         */
        function load($files, string $type) {
            if (!is_array($files)) $files = [$files];

            foreach ($files as $file) {
                if ($file == "") continue;
                if (!(substr($file, 0, 2) == '//'
                    || substr($file, 0, 4) == "http"))
                    $file = BASE_URL . $file;
                switch ($type) {
                    case 'css':
                        echo "<link rel='stylesheet' type='text/css' href='$file'>";
                        break;
                    case 'js':
                        echo "<script type='text/javascript' src='$file'></script>";
                        break;
                }
            }
        }

        /**
         * Load html meta ["metaname" => "value"]
         * @param array $metas
         */
        function loadMeta(array $metas) {
            foreach ($metas as $key => $value) { ?>
                <meta name="<?= $key ?>" content="<?= $value ?>">
            <?php }
        }

        /**
         * Load css files
         * @param string|string[] $files
         */
        function loadStyles($files) {
            load($files, 'css');
        }

        /**
         * Load js files
         * @param string|string[] $files
         */
        function loadScripts($files) {
            load($files, 'js');
        }

        $this->setContentType("text/html");
        extract($params);
        require_once $this->_viewsPath != null ? $this->_viewsPath."/".$viewPath : $viewPath; // if no file => error (intentional)
        $this->stopExec();
    }

    /**
     * Send data
     * @param int|string|array $data
     * @param boolean $jsonEncode default encode in JSON
     * @param string $contentType
     * @param boolean $stopScript to send more data
     */
    public function send($data, bool $jsonEncode = true, $contentType = null, bool $stopScript = true) {
        if ($contentType == null)
            $contentType = is_array($data) ? "application/json" : (new finfo(FILEINFO_MIME))->buffer((string)$data);
        $this->setContentType($contentType);
        echo !$jsonEncode ? $data : json_encode($data);

        if ($stopScript) $this->stopExec();
    }

    /**
     * @param string $file
     * @param string|null $contentType if you want to force the MIME Type
     */
    public function sendFile(string $file, $contentType = null) {
        if (file_exists($file)) {
            if ($contentType == null) {
                $contentType = mime_content_type($file);
                if ($contentType == "text/plain") {
                    $mimeTypes = json_decode(file_get_contents(PRIVATE_FOLDER . 'Class/MIME_TYPES.json'), true);
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    $contentType = isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : "text/plain";
                }
            }
            $this->setContentType($contentType);
            readfile($file);
        } else
            echo json_encode("Error: no file at " . $file);
        $this->stopExec();
    }

    /**
     * Redirect to an URL
     * @param string $url
     * @param bool $local
     */
    public function redirect(string $url, bool $local = true) {
        $this->setHeader("Location: " . ($local ? $this->_baseUrl : '') . $url);
        $this->stopExec();
    }

    /**
     * Set a content type for the header
     * @param $contentType
     */
    private function setContentType(string $contentType) {
        $this->setHeader("Content-type: " . $contentType);
    }

    /**
     * @param string $stringHeader
     * @param bool $replace
     * @param null|int $http_response_code
     */
    public function setHeader(string $header, bool $replace = true, $http_response_code = null) {
        header($header, $replace, $http_response_code);
    }
}