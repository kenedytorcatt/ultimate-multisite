<?php
/**
 * Tests for Template_Library class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Template_Library;

use WP_UnitTestCase;

/**
 * Test class for Template_Library.
 */
class Template_Library_Test extends WP_UnitTestCase {

	/**
	 * @var Template_Library
	 */
	private $library;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->library = Template_Library::get_instance();
		$this->library->init();
	}

	/**
	 * Test get_instance returns a Template_Library instance.
	 */
	public function test_get_instance_returns_instance(): void {
		$this->assertInstanceOf(Template_Library::class, $this->library);
	}

	/**
	 * Test get_instance returns the same instance (singleton).
	 */
	public function test_get_instance_is_singleton(): void {
		$instance1 = Template_Library::get_instance();
		$instance2 = Template_Library::get_instance();

		$this->assertSame($instance1, $instance2);
	}

	/**
	 * Test get_repository returns a Template_Repository instance.
	 */
	public function test_get_repository_returns_instance(): void {
		$repository = $this->library->get_repository();

		$this->assertInstanceOf(Template_Repository::class, $repository);
	}

	/**
	 * Test is_template_installed returns false for unknown slug.
	 */
	public function test_is_template_installed_returns_false_for_unknown(): void {
		$result = $this->library->is_template_installed('nonexistent-template-slug-xyz');

		$this->assertFalse($result);
	}

	/**
	 * Test clear_cache returns bool.
	 */
	public function test_clear_cache_returns_bool(): void {
		$result = $this->library->clear_cache();

		$this->assertIsBool($result);
	}
}
