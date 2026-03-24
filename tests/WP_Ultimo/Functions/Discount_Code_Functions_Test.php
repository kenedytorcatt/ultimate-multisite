<?php
/**
 * Tests for discount code functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for discount code functions.
 */
class Discount_Code_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_discount_code returns false for nonexistent.
	 */
	public function test_get_discount_code_nonexistent(): void {

		$result = wu_get_discount_code(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_discount_code_by_code returns false for nonexistent.
	 */
	public function test_get_discount_code_by_code_nonexistent(): void {

		$result = wu_get_discount_code_by_code('NONEXISTENT_CODE');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_discount_codes returns array.
	 */
	public function test_get_discount_codes_returns_array(): void {

		$result = wu_get_discount_codes();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_discounted_price with percentage discount.
	 */
	public function test_get_discounted_price_percentage(): void {

		$result = wu_get_discounted_price(100.00, 10, 'percentage', false);

		$this->assertEquals(90.00, $result);
	}

	/**
	 * Test wu_get_discounted_price with absolute discount.
	 */
	public function test_get_discounted_price_absolute(): void {

		$result = wu_get_discounted_price(100.00, 25, 'absolute', false);

		$this->assertEquals(75.00, $result);
	}

	/**
	 * Test wu_get_discounted_price with formatting.
	 */
	public function test_get_discounted_price_formatted(): void {

		$result = wu_get_discounted_price(100.00, 10, 'percentage', true);

		$this->assertEquals('90.00', $result);
	}

	/**
	 * Test wu_get_discounted_price with 100% discount.
	 */
	public function test_get_discounted_price_full_percentage(): void {

		$result = wu_get_discounted_price(50.00, 100, 'percentage', false);

		$this->assertEquals(0.00, $result);
	}

	/**
	 * Test wu_get_discounted_price with 50% discount.
	 */
	public function test_get_discounted_price_half(): void {

		$result = wu_get_discounted_price(200.00, 50, 'percentage', false);

		$this->assertEquals(100.00, $result);
	}

	/**
	 * Test wu_create_discount_code creates a discount code.
	 */
	public function test_create_discount_code(): void {

		$code = 'TESTCODE' . wp_rand();

		$discount = wu_create_discount_code([
			'name'            => 'Test Discount',
			'code'            => $code,
			'value'           => 10,
			'type'            => 'percentage',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($discount);
		$this->assertInstanceOf(\WP_Ultimo\Models\Discount_Code::class, $discount);
	}

	/**
	 * Test wu_get_discount_code_by_code retrieves created code.
	 */
	public function test_create_discount_code_returns_model(): void {

		$code = 'RETRIEVE' . wp_rand();

		$discount = wu_create_discount_code([
			'name'            => 'Retrieve Test',
			'code'            => $code,
			'value'           => 20,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($discount);
		$this->assertInstanceOf(\WP_Ultimo\Models\Discount_Code::class, $discount);
		$this->assertEquals('percentage', $discount->get_type());
	}
}
