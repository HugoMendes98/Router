<?php

// TODO: can disable time incrementing and integrate this
class Session {

	private $_session;
	private $_increment = true;

	private $key = "";
	private $timeout = 0;

	/**
	 * @return int
	 */
	public function getTimeout(): int {
		return $this->timeout;
	}

	/**
	 * Setting this option will automatically activates the incrementing of timeout
	 * @param int $timeout
	 */
	public function setTimeout(int $timeout): void {
		if ($timeout < 0) $timeout = 0;
		$this->timeout = $timeout;
		$this->_increment = true;
		@session_set_cookie_params($timeout);

		$this->_session["__end__"] = $timeout > 0 ?
			time() + $timeout : 0;
	}

	/**
	 * Active the incrementation for the current session only
	 * @param bool $increment
	 */
	public function incrementTimeout(bool $increment): void {
		if ($increment !== $this->_increment) {
			if ($increment)
				$this->setTimeout($this->getTimeout());
			else {
				$this->_session["__end__"] -= $this->getTimeout();
				$this->_increment = false;
			}
		}
	}

	/**
	 * Return the start of the session
	 * @return int
	 */
	public function getStartTime(): int {
		return $this->_session["__start__"];
	}

	/**
	 * @return int
	 */
	public function getEndTime(): int {
		return $this->_session["__end__"] ?: 0;
	}


	/**
	 * Session constructor.
	 * @param string $key 'unique' key for a site
	 */
	public function __construct(string $key) {
		$this->key = $key;
	}

	/**
	 * Already called by the constructor
	 * @param array $params same as session_start()
	 * @return bool false if the session has been theft
	 */
	public function init($params = []) {
		@session_start($params);

		if (isset($params["cookie_lifetime"]))
			$this->timeout = $params["cookie_lifetime"];

		if (!isset($_SESSION[$this->key]))
			$_SESSION[$this->key] = [
				"session" => [],
				"__headers__" => @$_SERVER['HTTP_USER_AGENT'] . @$_SERVER['HTTP_HOST'],
				"__start__" => time()
			];
		else if ($_SESSION[$this->key]["__headers__"] !== @$_SERVER['HTTP_USER_AGENT'] . @$_SERVER['HTTP_HOST']) {
			// block session, not destroy (session theft)
			$this->_session = [
				"session" => [],
				"__start__" => time(),
				"__error__" => true
			];
			return false;
		}

		$this->_session = &$_SESSION[$this->key];

		// Test Session
		if (isset($this->_session["__end__"])) {
			$end = $this->_session["__end__"];
			if ($end > 0 && $end - time() < 0) {
				$this->destroy();
				$this->init($params); // recreate session
			}
		}

		$this->setTimeout($this->timeout);
		return true;
	}

	/**
	 * Get a value
	 * @param string|string[] $keys empty array for all
	 * @return object|null
	 */
	public function get($keys) {
		if (is_string($keys))
			$keys = [$keys];

		$lastLevel = $this->_session["session"];
		foreach ($keys as $key) {
			if (!isset($lastLevel[$key])) {
				//TODO: show warning? trigger_error("Nothing found at '[" . implode($keys, ', ') . "]'");
				return null;
			}
			$lastLevel = $lastLevel[$key];
		}

		return $lastLevel;
	}

	/**
	 * Set a value
	 * @param string|string[] $keys
	 * @param $value
	 */
	public function set($keys, $value) {
		if (is_string($keys))
			$keys = [$keys];

		$lastLevel = &$this->_session["session"];
		foreach ($keys as $key)
			$lastLevel = &$lastLevel[$key];
		$lastLevel = $value;
	}


	/**
	 * Destroy the session
	 */
	public function destroy(): void {
		unset($this->_session);
		if (!$_SESSION[$this->key]["__error__"]) // Dont delete good session
			unset($_SESSION[$this->key]);
	}
}