<?php
/**
 * Tests for product functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for product functions.
 */
class Product_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_product returns false for nonexistent ID.
	 */
	public function test_get_product_nonexistent_id(): void {

		$result = wu_get_product(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_product returns false for nonexistent slug.
	 */
	public function test_get_product_nonexistent_slug(): void {

		$result = wu_get_product('nonexistent-slug-xyz');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_products returns array.
	 */
	public function test_get_products_returns_array(): void {

		$result = wu_get_products();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_plans returns array.
	 */
	public function test_get_plans_returns_array(): void {

		$result = wu_get_plans();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_plans_as_options returns array.
	 */
	public function test_get_plans_as_options_returns_array(): void {

		$result = wu_get_plans_as_options();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_product_by_slug returns false for nonexistent.
	 */
	public function test_get_product_by_slug_nonexistent(): void {

		$result = wu_get_product_by_slug('nonexistent-product-slug');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_product_by returns false for nonexistent.
	 */
	public function test_get_product_by_nonexistent(): void {

		$result = wu_get_product_by('id', 999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_create_product creates a product.
	 */
	public function test_create_product(): void {

		$product = wu_create_product([
			'name'            => 'Test Product',
			'slug'            => 'test-product-func-' . wp_rand(),
			'type'            => 'plan',
			'amount'          => 19.99,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($product);
		$this->assertInstanceOf(\WP_Ultimo\Models\Product::class, $product);
		$this->assertEquals('Test Product', $product->get_name());
	}

	/**
	 * Test wu_get_product retrieves by ID.
	 */
	public function test_get_product_by_id(): void {

		$product = wu_create_product([
			'name'            => 'Retrieve Test',
			'slug'            => 'retrieve-test-' . wp_rand(),
			'type'            => 'plan',
			'amount'          => 29.99,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($product);

		$retrieved = wu_get_product($product->get_id());

		$this->assertNotFalse($retrieved);
		$this->assertEquals($product->get_id(), $retrieved->get_id());
	}

	/**
	 * Test wu_get_product retrieves by slug.
	 */
	public function test_get_product_by_slug_string(): void {

		$slug = 'slug-test-product-' . wp_rand();

		$product = wu_create_product([
			'name'            => 'Slug Test',
			'slug'            => $slug,
			'type'            => 'plan',
			'amount'          => 39.99,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($product);

		$retrieved = wu_get_product($slug);

		$this->assertNotFalse($retrieved);
		$this->assertEquals($product->get_id(), $retrieved->get_id());
	}

	/**
	 * Test wu_is_plan_type returns true for plan.
	 */
	public function test_is_plan_type_plan(): void {

		$this->assertTrue(wu_is_plan_type('plan'));
	}

	/**
	 * Test wu_is_plan_type returns false for addon.
	 */
	public function test_is_plan_type_addon(): void {

		$this->assertFalse(wu_is_plan_type('addon'));
	}

	/**
	 * Test wu_has_independent_billing_cycle returns false by default.
	 */
	public function test_has_independent_billing_cycle_default(): void {

		$this->assertFalse(wu_has_independent_billing_cycle('plan'));
		$this->assertFalse(wu_has_independent_billing_cycle('addon'));
	}

	/**
	 * Test wu_segregate_products separates plan from addons.
	 */
	public function test_segregate_products(): void {

		$plan = wu_create_product([
			'name'            => 'Segregate Plan',
			'slug'            => 'segregate-plan-' . wp_rand(),
			'type'            => 'plan',
			'amount'          => 10.00,
			'skip_validation' => true,
		]);

		$addon = wu_create_product([
			'name'            => 'Segregate Addon',
			'slug'            => 'segregate-addon-' . wp_rand(),
			'type'            => 'addon',
			'amount'          => 5.00,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($plan);
		$this->assertNotWPError($addon);

		$result = wu_segregate_products([$plan, $addon]);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertInstanceOf(\WP_Ultimo\Models\Product::class, $result[0]);
		$this->assertEquals($plan->get_id(), $result[0]->get_id());
		$this->assertCount(1, $result[1]);
	}

	/**
	 * Test wu_segregate_products with no plan.
	 */
	public function test_segregate_products_no_plan(): void {

		$addon = wu_create_product([
			'name'            => 'Only Addon',
			'slug'            => 'only-addon-' . wp_rand(),
			'type'            => 'addon',
			'amount'          => 5.00,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($addon);

		$result = wu_segregate_products([$addon]);

		$this->assertFalse($result[0]);
		$this->assertCount(1, $result[1]);
	}
}
