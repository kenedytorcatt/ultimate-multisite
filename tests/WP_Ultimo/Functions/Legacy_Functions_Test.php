<?php
/**
 * Tests for legacy functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for legacy functions.
 */
class Legacy_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_create_html_attributes_from_array with empty array.
	 */
	public function test_create_html_attributes_empty(): void {

		$result = wu_create_html_attributes_from_array([]);

		$this->assertEquals('', $result);
	}

	/**
	 * Test wu_create_html_attributes_from_array with string values.
	 */
	public function test_create_html_attributes_string_values(): void {

		$result = wu_create_html_attributes_from_array([
			'class' => 'my-class',
			'id'    => 'my-id',
		]);

		$this->assertStringContainsString('class="my-class"', $result);
		$this->assertStringContainsString('id="my-id"', $result);
	}

	/**
	 * Test wu_create_html_attributes_from_array with boolean true.
	 */
	public function test_create_html_attributes_boolean_true(): void {

		$result = wu_create_html_attributes_from_array([
			'required' => true,
			'disabled' => true,
		]);

		$this->assertStringContainsString('required', $result);
		$this->assertStringContainsString('disabled', $result);
	}

	/**
	 * Test wu_create_html_attributes_from_array with boolean false.
	 */
	public function test_create_html_attributes_boolean_false(): void {

		$result = wu_create_html_attributes_from_array([
			'required' => false,
		]);

		$this->assertStringNotContainsString('required', $result);
	}

	/**
	 * Test wu_create_html_attributes_from_array with mixed values.
	 */
	public function test_create_html_attributes_mixed(): void {

		$result = wu_create_html_attributes_from_array([
			'class'    => 'test',
			'required' => true,
			'disabled' => false,
		]);

		$this->assertStringContainsString('class="test"', $result);
		$this->assertStringContainsString('required', $result);
		$this->assertStringNotContainsString('disabled', $result);
	}

	/**
	 * Test WU_Signup function returns Legacy_Checkout instance.
	 */
	public function test_wu_signup_returns_instance(): void {

		$result = \WU_Signup();

		$this->assertInstanceOf(\WP_Ultimo\Checkout\Legacy_Checkout::class, $result);
	}

	/**
	 * Test WU_Signup returns same instance (singleton).
	 */
	public function test_wu_signup_singleton(): void {

		$this->assertSame(\WU_Signup(), \WU_Signup());
	}
}
