<?php
/**
 * Tests for membership functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for membership functions.
 */
class Membership_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_membership returns false for nonexistent.
	 */
	public function test_get_membership_nonexistent(): void {

		$result = wu_get_membership(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_membership_by returns false for nonexistent.
	 */
	public function test_get_membership_by_nonexistent(): void {

		$result = wu_get_membership_by('id', 999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_membership_by_hash returns false for nonexistent.
	 */
	public function test_get_membership_by_hash_nonexistent(): void {

		$result = wu_get_membership_by_hash('nonexistent_hash');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_memberships returns array.
	 */
	public function test_get_memberships_returns_array(): void {

		$result = wu_get_memberships();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_memberships with search query.
	 */
	public function test_get_memberships_with_search(): void {

		$result = wu_get_memberships(['search' => 'nonexistent']);

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_create_membership creates a membership.
	 */
	public function test_create_membership(): void {

		$customer = wu_create_customer([
			'user_id'         => self::factory()->user->create(),
			'skip_validation' => true,
		]);

		$product = wu_create_product([
			'name'            => 'Test Plan',
			'slug'            => 'test-plan-membership-' . wp_rand(),
			'type'            => 'plan',
			'amount'          => 29.99,
			'skip_validation' => true,
		]);

		$membership = wu_create_membership([
			'customer_id'     => $customer->get_id(),
			'plan_id'         => $product->get_id(),
			'status'          => 'active',
			'amount'          => 29.99,
			'currency'        => 'USD',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($membership);
		$this->assertInstanceOf(\WP_Ultimo\Models\Membership::class, $membership);
		$this->assertEquals('active', $membership->get_status());
	}

	/**
	 * Test wu_get_membership_by_customer_gateway_id returns false when not found.
	 */
	public function test_get_membership_by_customer_gateway_id_not_found(): void {

		$result = wu_get_membership_by_customer_gateway_id('cus_nonexistent', ['stripe']);

		$this->assertFalse($result);
	}
}
