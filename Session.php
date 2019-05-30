<?php

// TODO: can disable time incrementing and integrate this
class Session {

	private $key = "";
	private $timeout = 0;

	/**
	 * @return int
	 */
	public function getTimeout(): int {
		return $this->timeout;
	}

	/**
	 * @param int $timeout
	 */
	public function setTimeout(int $timeout): void {
		if ($timeout < 0) $timeout = 0;
		$this->timeout = $timeout;
		session_set_cookie_params($timeout);

		$_SESSION[$this->key]["__end__"] = $timeout > 0 ?
			time() + $timeout : 0;
	}

	/**
	 * Return the start of the session
	 * @return int
	 */
	public function getStartTime(): int {
		return $_SESSION[$this->key]["__start__"];
	}

	/**
	 * @return int
	 */
	public function getEndTime(): int {
		return $_SESSION[$this->key]["__end__"];
	}


	public function __construct(string $key) {
		$this->key = $key;
	}

	/**
	 * @param array $params
	 * @return bool false if the session has been theft
	 */
	public function init($params = []) {
		session_start($params);

		// Test Session
		if (isset($_SESSION[$this->key]) && isset($_SESSION[$this->key]["__end__"])) {
			$end = $_SESSION[$this->key]["__end__"];
			if ($end > 0 && $end - time() < 0)
				$this->destroy();
		}

		if (isset($params["cookie_lifetime"]))
			$this->timeout = $params["cookie_lifetime"];

		if (!isset($_SESSION[$this->key]))
			$_SESSION[$this->key] = [
				"session" => [],
				"__headers__" => @$_SERVER['HTTP_USER_AGENT'] . @$_SERVER['HTTP_HOST'],
				"__start__" => time()
			];
		else if ($_SESSION[$this->key]["__headers__"] !== @$_SERVER['HTTP_USER_AGENT'] . @$_SERVER['HTTP_HOST']) {
			// TODO: block session, not destroy (session theft)
			return false;
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

		$lastLevel = $_SESSION[$this->key]["session"];
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

		$lastLevel = &$_SESSION[$this->key]["session"];
		foreach ($keys as $key)
			$lastLevel = &$lastLevel[$key];
		$lastLevel = $value;
	}


	/**
	 * Destroy the session
	 */
	public function destroy(): void {
		unset($_SESSION[$this->key]);
	}
}