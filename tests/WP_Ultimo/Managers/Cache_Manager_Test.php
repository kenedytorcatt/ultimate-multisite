<?php
/**
 * Unit tests for Cache_Manager.
 *
 * @package WP_Ultimo\Tests\Managers
 */

namespace WP_Ultimo\Tests\Managers;

use WP_Ultimo\Managers\Cache_Manager;

/**
 * Unit tests for Cache_Manager.
 */
class Cache_Manager_Test extends \WP_UnitTestCase {

	use Manager_Test_Trait;

	/**
	 * Get the manager class name.
	 *
	 * @return string
	 */
	protected function get_manager_class(): string {
		return Cache_Manager::class;
	}

	/**
	 * Get the expected slug.
	 *
	 * @return string|null
	 */
	protected function get_expected_slug(): ?string {
		return null;
	}

	/**
	 * Get the expected model class.
	 *
	 * @return string|null
	 */
	protected function get_expected_model_class(): ?string {
		return null;
	}

	/**
	 * Test flush_known_caches runs without error.
	 */
	public function test_flush_known_caches_does_not_throw(): void {

		$manager = $this->get_manager_instance();

		// Should not throw even when no caching plugins are active.
		$manager->flush_known_caches();

		$this->assertTrue(true);
	}

	/**
	 * Test flush_known_caches fires action hook.
	 */
	public function test_flush_known_caches_fires_action(): void {

		$manager = $this->get_manager_instance();

		$fired = false;

		add_action('wu_flush_known_caches', function () use (&$fired) {
			$fired = true;
		});

		$manager->flush_known_caches();

		$this->assertTrue($fired);
	}

	/**
	 * Test that cache flush methods exist for known plugins.
	 */
	public function test_cache_flush_methods_exist(): void {

		$manager    = $this->get_manager_instance();
		$reflection = new \ReflectionClass($manager);

		$all_methods = $reflection->getMethods();

		$flush_methods = array_map(
			fn($m) => $m->getName(),
			array_filter($all_methods, fn($m) => str_ends_with($m->getName(), '_cache_flush'))
		);

		// Should have at least the known caching plugin methods (protected)
		$this->assertNotEmpty($flush_methods);
		$this->assertContains('wp_engine_cache_flush', $flush_methods);
		$this->assertContains('wp_rocket_cache_flush', $flush_methods);
		$this->assertContains('wp_super_cache_flush', $flush_methods);
		$this->assertContains('wp_fastest_cache_flush', $flush_methods);
		$this->assertContains('w3_total_cache_flush', $flush_methods);
		$this->assertContains('hummingbird_cache_flush', $flush_methods);
		$this->assertContains('wp_optimize_cache_flush', $flush_methods);
		$this->assertContains('comet_cache_flush', $flush_methods);
		$this->assertContains('litespeed_cache_flush', $flush_methods);
	}

	/**
	 * Test individual cache flush methods don't throw when plugins aren't active.
	 */
	public function test_individual_cache_flush_methods_safe(): void {

		$manager    = $this->get_manager_instance();
		$reflection = new \ReflectionClass($manager);

		$methods = [
			'wp_engine_cache_flush',
			'wp_rocket_cache_flush',
			'wp_super_cache_flush',
			'wp_fastest_cache_flush',
			'w3_total_cache_flush',
			'hummingbird_cache_flush',
			'wp_optimize_cache_flush',
			'comet_cache_flush',
			'litespeed_cache_flush',
		];

		foreach ($methods as $method_name) {
			$method = $reflection->getMethod($method_name);

			if (PHP_VERSION_ID < 80100) {
				$method->setAccessible(true);
			}

			// Should not throw when the caching plugin is not active
			$method->invoke($manager);
		}

		$this->assertTrue(true);
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$instance1 = Cache_Manager::get_instance();
		$instance2 = Cache_Manager::get_instance();

		$this->assertSame($instance1, $instance2);
	}
}
