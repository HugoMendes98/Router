<?php

require_once 'Response.php';
require_once 'Session.php';

/**
 * Class Router
 * Simply routes manager
 */
class Router {
    const GET = 'GET';
    const POST = 'POST';

    private $_baseUrl;
    private $_subRoute;
    private $_url;
    private $_method;
    private $_session;

    private $_viewsPath = null;

    /**
     * Return the base URl of the server
     * @return string
     */
    public function getBaseURL() {
        return $this->_baseUrl;
    }

    /**
     * Return the url
     * @return string
     */
    public function getURL() {
        return $this->_url;
    }

    /**
     * Return the method (GET|POST)
     * @return string
     */
    public function getMethod() {
        return $this->_method;
    }

    /**
     * Return the path to the views
     * @return string
     */
    public function getViewsPath() {
        return $this->_viewsPath;
    }

    /**
     * Set the path to the views
     * @param string $viewPath
     * @return Router
     */
    public function setViewsPath(string $viewPath) {
        $this->_viewsPath = $viewPath;
        return $this;
    }

    /**
     * Return the session
     * @return Session
     */
    public function Session(): Session {
        return $this->_session;
    }

    /**
     * Create the router
     * @param string $subRoute for an inner path
     * @param Session $session use existing session
     */
    public function __construct(string $subRoute = "", Session $session = null) {
        $this->_baseUrl = pathinfo($_SERVER['PHP_SELF'], PATHINFO_DIRNAME);
        $this->_url = $this->removeSlash(str_replace($this->_baseUrl, "" , $_SERVER["REQUEST_URI"]));
        $this->_method = $_SERVER["REQUEST_METHOD"]; //GET || POST
        $this->_session = $session !== null ? $session : new Session($this->_baseUrl);

        $this->_subRoute = $subRoute;
    }

    /**
     * Use the start path given to redirect to a directory
     * @param string $path
     * @param string $folder
     * @param string|null $contentType
     * @param bool $errorNotFound Raise an error if file not found
     * @return $this
     */
    public function byEntry(string $path, string $folder, $contentType = null, $errorNotFound = false) {
        $this->get($this->removeSlash($path) . '/*', function (Response $res, $args) use ($folder, $contentType, $errorNotFound) {
            $file = $folder . '/' . $args[0];
            if ($errorNotFound || file_exists($file))
                $res->sendFile($file, $contentType);
        });
        return $this;
    }

    /**
     * Use the extension of the uri to redirect
     * @param string|string[] $extension
     * @param string $folder
     * @param string|null $contentType
     * @param bool $errorNotFound Raise an error if file not found
     * @return Router $this
     */
    public function byExt($extension, string $folder, $contentType = null, $errorNotFound = false) {
        $extension = is_array($extension) ? $extension : [$extension];
        if (in_array(pathinfo($this->_url, PATHINFO_EXTENSION), $extension)) {
            if ($errorNotFound || file_exists($folder . $this->_url))
                (new Response($this))->sendFile($folder . $this->_url, $contentType);
        }
        return $this;
    }

    private function removeSlash(string $string) {
        return substr($string, -1) === '/' ? substr($string, 0, -1) : $string;
    }

    /**
     * Search for parameters in the uri (':param' || '*')
     * @param string $string
     * @return string
     */
    private function doPattern(string $string) {
        preg_match('/([\w\/]*)(\:[a-zA-Z]+|\*)([\w\/\:\.\*]*)/', $string, $matches);

        $wanted = $matches[2];
        $after = $matches[3];
        $middle = '([/\w\.\-\_]*)'; // remplace * => + if :par

        if (strpos($after, '*') !== false || strpos($after, ':') !== false)
            $after = $this->doPattern($after);
        if (strpos($wanted, ':') !== false)
            $middle = '(?P<' . substr($wanted, 1) . '>\w+)';

        return $matches[1] . $middle . $after;
    }

    /**
     * Read uri to find parameters
     * @param string $uri
     * @param callable(Response?, Array?) $callback
     */
    private function testUrl(string $uri, callable $callback) {
        // To Do (improve)
        $uri = $this->removeSlash($this->_subRoute . $uri);

        $url = $this->_url;

        { // Remove GET parameters
            $pos = strpos($url, "?");
            if ($pos !== false)
                $url = substr($url, 0, $pos);
        }

        $isOk = $uri == $url;

        $matches = [];
        if (!$isOk && (strpos($uri, '*') !== false || strpos($uri, ':') !== false )) {
            $pattern = str_replace('/', '\/', $this->doPattern($uri));
            $isOk = preg_match('/^' . $pattern . '$/', $url, $matches);
        } else
            $matches = [$this->_url];

        if ($isOk) {
            if(!empty($matches))
                array_splice($matches, 0, 1);
            $callback(new Response($this), $matches);
        }
    }

    /**
     * @param string $uri
     * @param callable(Request, Response) $callback
     * @return Router $this
     */
    public function get(string $uri, callable $callback) {
        if ($this->_method == self::GET)
            $this->testUrl($uri, $callback);
        return $this;
    }

    /**
     * @param string $uri
     * @param callable $callback
     * @return Router $this
     */
    public function post(string $uri, callable $callback) {
        if ($this->_method == self::POST)
            $this->testUrl($uri, $callback);
        return $this;
    }

    /**
     * @param string $uri
     * @param callable $callback
     * @return Router $this
     */
    public function on(string $uri, callable $callback) {
        $this->testUrl($uri, $callback);
        return $this;
    }

    /**
     *
     * @param string $url
     * @param callable(Router, ...mixed)|string $routesOrCallable callable or path to file with callable
     * @param mixed ...$params to send to the callable
     * @return Router $this
     */
    public function use(string $url, $callback, ...$params) {
        if (is_callable($callback) || (is_string($callback) && file_exists($callback))) {
            $subURL = $this->_subRoute . $url;
            for ($length = 0; $length < strlen($subURL); $length++)
                if (in_array($subURL[$length], ['*', ':'])) break;

            if (substr($this->_url, 0, $length) == substr($subURL, 0, $length)) {
                $router = new Router($subURL, $this->Session());
            	if (is_string($callback) && file_exists($callback))
					(require $callback)($router, ...$params);
				else
					$callback($router, ...$params);
			}
        }
        return $this;
    }
}