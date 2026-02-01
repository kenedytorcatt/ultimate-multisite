<?php
/**
 * Unit tests for Limitation_Manager.
 *
 * @package WP_Ultimo\Tests\Managers
 */

namespace WP_Ultimo\Tests\Managers;

use WP_Ultimo\Managers\Limitation_Manager;

class Limitation_Manager_Test extends \WP_UnitTestCase {

	use Manager_Test_Trait;

	protected function get_manager_class(): string {
		return Limitation_Manager::class;
	}

	protected function get_expected_slug(): ?string {
		return null;
	}

	protected function get_expected_model_class(): ?string {
		return null;
	}

	/**
	 * Test get_all_plugins returns an array.
	 */
	public function test_get_all_plugins_returns_array(): void {

		$manager = $this->get_manager_instance();
		$plugins = $manager->get_all_plugins();

		$this->assertIsArray($plugins);
	}

	/**
	 * Test get_all_themes returns an array.
	 */
	public function test_get_all_themes_returns_array(): void {

		$manager = $this->get_manager_instance();
		$themes  = $manager->get_all_themes();

		$this->assertIsArray($themes);
	}

	/**
	 * Test get_object_type returns the correct type for a Product model.
	 */
	public function test_get_object_type_with_product(): void {

		$manager = $this->get_manager_instance();

		$product = new \WP_Ultimo\Models\Product();
		$type    = $manager->get_object_type($product);

		$this->assertEquals('product', $type);
	}

	/**
	 * Test get_object_type returns false for unknown objects.
	 */
	public function test_get_object_type_with_unknown(): void {

		$manager = $this->get_manager_instance();
		$type    = $manager->get_object_type(new \stdClass());

		$this->assertFalse($type);
	}
}
