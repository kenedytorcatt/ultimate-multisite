<?php
/**
 * Tests for Ajax class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

use WP_UnitTestCase;

/**
 * Test class for Ajax.
 */
class Ajax_Test extends WP_UnitTestCase {

	/**
	 * @var Ajax
	 */
	private Ajax $ajax;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {

		parent::set_up();
		$this->ajax = Ajax::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(Ajax::class, $this->ajax);
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(Ajax::get_instance(), Ajax::get_instance());
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$this->ajax->init();

		$this->assertGreaterThan(0, has_action('wu_ajax_wu_search', [$this->ajax, 'search_models']));
		$this->assertGreaterThan(0, has_action('in_admin_footer', [$this->ajax, 'render_selectize_templates']));
		$this->assertGreaterThan(0, has_action('wp_ajax_wu_list_table_fetch_ajax_results', [$this->ajax, 'refresh_list_table']));
	}

	/**
	 * Test get_table_class_name via reflection.
	 */
	public function test_get_table_class_name(): void {

		$reflection = new \ReflectionClass($this->ajax);
		$method     = $reflection->getMethod('get_table_class_name');

		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		$result = $method->invoke($this->ajax, 'line_item_list_table');

		$this->assertEquals('Line_Item_List_Table', $result);
	}

	/**
	 * Test get_table_class_name with payment table.
	 */
	public function test_get_table_class_name_payment(): void {

		$reflection = new \ReflectionClass($this->ajax);
		$method     = $reflection->getMethod('get_table_class_name');

		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		$result = $method->invoke($this->ajax, 'payment_list_table');

		$this->assertEquals('Payment_List_Table', $result);
	}

	/**
	 * Test get_table_class_name with customer table.
	 */
	public function test_get_table_class_name_customer(): void {

		$reflection = new \ReflectionClass($this->ajax);
		$method     = $reflection->getMethod('get_table_class_name');

		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		$result = $method->invoke($this->ajax, 'customer_list_table');

		$this->assertEquals('Customer_List_Table', $result);
	}
}
