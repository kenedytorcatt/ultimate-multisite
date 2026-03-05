<?php
/**
 * WordPress-based PSR-16 Simple Cache implementation.
 *
 * @package WP_Ultimo
 * @subpackage SSO
 * @since 2.0.11
 */

namespace WP_Ultimo\SSO;

use Psr\SimpleCache\CacheInterface;

defined('ABSPATH') || exit;

/**
 * WordPress transient-based PSR-16 cache implementation.
 *
 * @since 2.4.13
 */
class WordPress_Simple_Cache implements CacheInterface {

	/**
	 * Cache key prefix.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Constructor.
	 *
	 * @param string $prefix Cache key prefix.
	 */
	public function __construct($prefix = 'wu_sso_') {
		$this->prefix = $prefix;
	}

	/**
	 * Fetches a value from the cache.
	 *
	 * @param string $key     The unique key of this item in the cache.
	 * @param mixed  $default Default value to return if the key does not exist.
	 * @return mixed The value of the item from the cache, or $default in case of cache miss.
	 */
	public function get($key, $default = null) {
		$value = get_site_transient($this->prefix . $key);
		return false !== $value ? $value : $default;
	}

	/**
	 * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
	 *
	 * @param string                 $key   The key of the item to store.
	 * @param mixed                  $value The value of the item to store.
	 * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item.
	 * @return bool True on success and false on failure.
	 */
	public function set($key, $value, $ttl = null) {
		$expiration = $this->convert_ttl_to_seconds($ttl);
		return set_site_transient($this->prefix . $key, $value, $expiration);
	}

	/**
	 * Delete an item from the cache by its unique key.
	 *
	 * @param string $key The unique cache key of the item to delete.
	 * @return bool True if the item was successfully removed. False if there was an error.
	 */
	public function delete($key) {
		return delete_site_transient($this->prefix . $key);
	}

	/**
	 * Wipes clean the entire cache's keys.
	 *
	 * @return bool True on success and false on failure.
	 */
	public function clear() {
		global $wpdb;

		// Delete all transients with our prefix.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
				$wpdb->esc_like('_site_transient_' . $this->prefix) . '%',
				$wpdb->esc_like('_site_transient_timeout_' . $this->prefix) . '%'
			)
		);

		return true;
	}

	/**
	 * Obtains multiple cache items by their unique keys.
	 *
	 * @param iterable $keys    A list of keys that can be obtained in a single operation.
	 * @param mixed    $default Default value to return for keys that do not exist.
	 * @return iterable A list of key => value pairs.
	 */
	public function getMultiple($keys, $default = null) {
		$values = array();

		foreach ($keys as $key) {
			$values[ $key ] = $this->get($key, $default);
		}

		return $values;
	}

	/**
	 * Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 * @param iterable               $values A list of key => value pairs for a multiple-set operation.
	 * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item.
	 * @return bool True on success and false on failure.
	 */
	public function setMultiple($values, $ttl = null) {
		$success = true;

		foreach ($values as $key => $value) {
			if (! $this->set($key, $value, $ttl)) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Deletes multiple cache items in a single operation.
	 *
	 * @param iterable $keys A list of string-based keys to be deleted.
	 * @return bool True if the items were successfully removed. False if there was an error.
	 */
	public function deleteMultiple($keys) {
		$success = true;

		foreach ($keys as $key) {
			if (! $this->delete($key)) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Determines whether an item is present in the cache.
	 *
	 * @param string $key The cache item key.
	 * @return bool
	 */
	public function has($key) {
		return false !== get_site_transient($this->prefix . $key);
	}

	/**
	 * Convert TTL to seconds.
	 *
	 * @param null|int|\DateInterval $ttl The TTL value.
	 * @return int Expiration time in seconds.
	 */
	protected function convert_ttl_to_seconds($ttl) {
		if (null === $ttl) {
			return 0; // No expiration.
		}

		if ($ttl instanceof \DateInterval) {
			$now    = new \DateTime();
			$future = $now->add($ttl);
			return $future->getTimestamp() - $now->getTimestamp();
		}

		return (int) $ttl;
	}
}
