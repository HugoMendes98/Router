<?php

require_once __DIR__ . '/Render.php';
require_once __DIR__ . '/Session.php';

class Response {

    private $_router;
    private $_viewsPath = null;

    /**
     * @return string
     */
    public function getBaseUrl() {
        return $this->_router->getBaseURL();
    }

	/**
	 * @return string
	 */
	public function getMethod(): string {
		return $this->_router->getMethod();
	}

    /**
     * Return the session
     * @return Session
     */
	public function Session(): Session {
        return $this->_router->Session();
    }

    /**
     * Response constructor.
     * @param Router
     */
    public function __construct(Router $router) {
        $this->_router = $router;
        $this->_viewsPath = $router->getViewsPath();
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
        $this->setContentType("text/html");
        extract($params);
        $_ = new Render($this->getBaseUrl());
        require_once $this->_viewsPath != null ? $this->_viewsPath."/".$viewPath : $viewPath; // if no file => error (intentional)
        $this->stopExec();
    }

    /**
     * Send data
     * @param int|string|array $data
     * @param int $statusCode
     * @param boolean $jsonEncode default encode in JSON
     * @param string $contentType
     * @param boolean $stopScript to send more data
     */
    public function send($data, int $statusCode = 200, bool $jsonEncode = true, $contentType = null, bool $stopScript = true) {
        if ($contentType == null)
            $contentType = is_array($data) ? "application/json" : (new finfo(FILEINFO_MIME))->buffer((string)$data);

        http_response_code($statusCode);
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
                    $mimeTypes = json_decode(file_get_contents(__DIR__ . '/mime_types.json'), true);
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    $contentType = isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : "text/plain";
                }
            }
            $this->setContentType($contentType);
            readfile($file);
        } else
            http_response_code(404);
        $this->stopExec();
    }

    /**
     * Redirect to an URL
     * @param string $url
     * @param bool $local
	 * @param bool $permant
     */
    public function redirect(string $url, bool $local = true, bool $permant = false) {
        $this->setHeader("Location: " . ($local ? $this->getBaseUrl() : '') . $url, true, $permant ? 301 : 302);
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
     * @param string $header
     * @param bool $replace
     * @param null|int $http_response_code
     */
    public function setHeader(string $header, bool $replace = true, $http_response_code = null) {
        header($header, $replace, $http_response_code);
    }
}