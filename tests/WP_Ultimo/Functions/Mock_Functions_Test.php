<?php
/**
 * Tests for model mocking functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for model mocking functions.
 */
class Mock_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_mock_site returns a Site model.
	 */
	public function test_mock_site(): void {

		$site = wu_mock_site();

		$this->assertInstanceOf(\WP_Ultimo\Models\Site::class, $site);
	}

	/**
	 * Test wu_mock_site with seed returns different title.
	 */
	public function test_mock_site_with_seed(): void {

		$site1 = wu_mock_site(1);
		$site2 = wu_mock_site(2);

		$this->assertInstanceOf(\WP_Ultimo\Models\Site::class, $site1);
		$this->assertInstanceOf(\WP_Ultimo\Models\Site::class, $site2);
	}

	/**
	 * Test wu_mock_membership returns a Membership model.
	 */
	public function test_mock_membership(): void {

		$membership = wu_mock_membership();

		$this->assertInstanceOf(\WP_Ultimo\Models\Membership::class, $membership);
	}

	/**
	 * Test wu_mock_product returns a Product model.
	 */
	public function test_mock_product(): void {

		$product = wu_mock_product();

		$this->assertInstanceOf(\WP_Ultimo\Models\Product::class, $product);
	}

	/**
	 * Test wu_mock_customer returns a Customer model.
	 */
	public function test_mock_customer(): void {

		$customer = wu_mock_customer();

		$this->assertInstanceOf(\WP_Ultimo\Models\Customer::class, $customer);
	}

	/**
	 * Test wu_mock_payment returns a Payment model.
	 */
	public function test_mock_payment(): void {

		$payment = wu_mock_payment();

		$this->assertInstanceOf(\WP_Ultimo\Models\Payment::class, $payment);
	}

	/**
	 * Test wu_mock_domain returns a Domain model.
	 */
	public function test_mock_domain(): void {

		$domain = wu_mock_domain();

		$this->assertInstanceOf(\WP_Ultimo\Models\Domain::class, $domain);
	}
}
