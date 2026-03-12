<?php
/**
 * Unit tests for Views.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

class Views_Test extends \WP_UnitTestCase {

	/**
	 * Get the singleton instance.
	 *
	 * @return Views
	 */
	protected function get_instance(): Views {

		return Views::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(Views::class, $this->get_instance());
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(
			Views::get_instance(),
			Views::get_instance()
		);
	}

	/**
	 * Test init registers the view_override filter.
	 */
	public function test_init_registers_filter(): void {

		$instance = $this->get_instance();

		$instance->init();

		$this->assertIsInt(has_filter('wu_view_override', [$instance, 'view_override']));
	}

	/**
	 * Test view_override returns original path when no override exists.
	 */
	public function test_view_override_returns_original_when_no_override(): void {

		$instance = $this->get_instance();

		$original = '/path/to/original/view.php';

		$result = $instance->view_override($original, 'nonexistent-view-xyz');

		$this->assertSame($original, $result);
	}

	/**
	 * Test custom_locate_template returns empty string for nonexistent template.
	 */
	public function test_custom_locate_template_returns_empty_for_nonexistent(): void {

		$instance = $this->get_instance();

		$result = $instance->custom_locate_template('nonexistent-template-xyz.php');

		$this->assertSame('', $result);
	}

	/**
	 * Test custom_locate_template accepts array of template names.
	 */
	public function test_custom_locate_template_accepts_array(): void {

		$instance = $this->get_instance();

		$result = $instance->custom_locate_template([
			'nonexistent-1.php',
			'nonexistent-2.php',
		]);

		$this->assertSame('', $result);
	}
}
