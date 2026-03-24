<?php
/**
 * Tests for invoice functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for invoice functions.
 */
class Invoice_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_invoice_template returns false.
	 */
	public function test_wu_get_invoice_template_returns_false(): void {

		$result = wu_get_invoice_template();

		$this->assertFalse($result);
	}
}
