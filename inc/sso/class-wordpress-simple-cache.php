<?php
/**
 * WordPress Simple Cache - PSR-16 Compatible Implementation
 *
 * Provides a PSR-16 CacheInterface implementation using WordPress transients.
 *
 * @package WP_Ultimo
 * @subpackage SSO
 * @since 2.0.11
 */

namespace WP_Ultimo\SSO;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * WordPress Simple Cache Class
 *
 * Implements PSR-16 CacheInterface using WordPress transients.
 *
 * @since 2.0.11
 */
class WordPress_Simple_Cache implements CacheInterface {

	/**
	 * Key prefix for namespacing cache entries.
	 *
	 * @since 2.0.11
	 * @var string
	 */
	protected $prefix;

	/**
	 * Constructor.
	 *
	 * @since 2.0.11
	 *
	 * @param string $prefix Optional key prefix for namespacing.
	 */
	public function __construct(string $prefix = '') {
		$this->prefix = $prefix;
	}

	/**
	 * Fetches a value from the cache.
	 *
	 * @since 2.0.11
	 *
	 * @param string $key     The unique key of this item in the cache.
	 * @param mixed  $default Default value to return if the key does not exist.
	 * @return mixed The value of the item from the cache, or $default in case of cache miss.
	 * @throws InvalidArgumentException If $key is not a legal value.
	 */
	public function get($key, $default = null) {
		$this->validateKey($key);
		$value = get_transient($this->prefix . $key);
		return false !== $value ? $value : $default;
	}

	/**
	 * Persists data in the cache.
	 *
	 * @since 2.0.11
	 *
	 * @param string                 $key   The key of the item to store.
	 * @param mixed                  $value The value of the item to store.
	 * @param null|int|\DateInterval $ttl   Optional. The TTL value in seconds.
	 * @return bool True on success, false on failure.
	 * @throws InvalidArgumentException If $key is not a legal value.
	 */
	public function set($key, $value, $ttl = null) {
		$this->validateKey($key);
		$expiration = $this->ttlToSeconds($ttl);
		return set_transient($this->prefix . $key, $value, $expiration);
	}

	/**
	 * Delete an item from the cache.
	 *
	 * @since 2.0.11
	 *
	 * @param string $key The unique cache key.
	 * @return bool True if removed, false on error.
	 * @throws InvalidArgumentException If $key is not a legal value.
	 */
	public function delete($key) {
		$this->validateKey($key);
		return delete_transient($this->prefix . $key);
	}

	/**
	 * Wipes clean the entire cache.
	 *
	 * Note: This deletes all transients with the configured prefix.
	 *
	 * @since 2.0.11
	 * @return bool True on success, false on failure.
	 */
	public function clear() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like('_transient_' . $this->prefix) . '%'
			)
		);
		// phpcs:enable

		if (!is_array($results)) {
			return false;
		}

		$success = true;
		foreach ($results as $result) {
			// Extract the transient name from option_name
			$transient_name = str_replace('_transient_', '', $result->option_name);
			if (!delete_transient($transient_name)) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Obtains multiple cache items.
	 *
	 * @since 2.0.11
	 *
	 * @param iterable $keys    A list of keys.
	 * @param mixed    $default Default value for missing keys.
	 * @return iterable A list of key => value pairs.
	 * @throws InvalidArgumentException If $keys is not iterable or contains illegal values.
	 */
	public function getMultiple($keys, $default = null) {
		if (!is_array($keys) && !($keys instanceof \Traversable)) {
			throw new class('Keys must be an array or Traversable') extends \Exception implements InvalidArgumentException {};
		}

		$result = [];
		foreach ($keys as $key) {
			$result[$key] = $this->get($key, $default);
		}

		return $result;
	}

	/**
	 * Persists multiple key => value pairs.
	 *
	 * @since 2.0.11
	 *
	 * @param iterable               $values A list of key => value pairs.
	 * @param null|int|\DateInterval $ttl    Optional. The TTL value in seconds.
	 * @return bool True on success, false on failure.
	 * @throws InvalidArgumentException If $values is not iterable or contains illegal values.
	 */
	public function setMultiple($values, $ttl = null) {
		if (!is_array($values) && !($values instanceof \Traversable)) {
			throw new class('Values must be an array or Traversable') extends \Exception implements InvalidArgumentException {};
		}

		$success = true;
		foreach ($values as $key => $value) {
			if (!$this->set($key, $value, $ttl)) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Deletes multiple cache items.
	 *
	 * @since 2.0.11
	 *
	 * @param iterable $keys A list of keys to delete.
	 * @return bool True if all removed, false otherwise.
	 * @throws InvalidArgumentException If $keys is not iterable or contains illegal values.
	 */
	public function deleteMultiple($keys) {
		if (!is_array($keys) && !($keys instanceof \Traversable)) {
			throw new class('Keys must be an array or Traversable') extends \Exception implements InvalidArgumentException {};
		}

		$success = true;
		foreach ($keys as $key) {
			if (!$this->delete($key)) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Checks if an item exists in cache.
	 *
	 * @since 2.0.11
	 *
	 * @param string $key The cache item key.
	 * @return bool True if exists, false otherwise.
	 * @throws InvalidArgumentException If $key is not a legal value.
	 */
	public function has($key) {
		$this->validateKey($key);
		return false !== get_transient($this->prefix . $key);
	}

	/**
	 * Validates a cache key.
	 *
	 * @since 2.0.11
	 *
	 * @param string $key The key to validate.
	 * @throws InvalidArgumentException If the key is invalid.
	 */
	protected function validateKey($key): void {
		if (!is_string($key)) {
			throw new class('Cache key must be a string') extends \Exception implements InvalidArgumentException {};
		}

		if (empty($key)) {
			throw new class('Cache key cannot be empty') extends \Exception implements InvalidArgumentException {};
		}

		// PSR-16 reserved characters: {}()/\@:
		if (preg_match('/[{}()\/\\@:]/', $key)) {
			throw new class('Cache key contains reserved characters') extends \Exception implements InvalidArgumentException {};
		}
	}

	/**
	 * Converts TTL to seconds.
	 *
	 * @since 2.0.11
	 *
	 * @param null|int|\DateInterval $ttl TTL value.
	 * @return int Seconds.
	 */
	protected function ttlToSeconds($ttl): int {
		if (null === $ttl) {
			return 0; // 0 means no expiration in WordPress transients
		}

		if ($ttl instanceof \DateInterval) {
			return (int) \DateTime::createFromFormat('U', '0')
				->add($ttl)
				->getTimestamp();
		}

		return (int) $ttl;
	}
}
