<?php
/**
 * Tests for customer functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for customer functions.
 */
class Customer_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_customer returns false for nonexistent.
	 */
	public function test_get_customer_nonexistent(): void {

		$result = wu_get_customer(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_customer_by returns false for nonexistent.
	 */
	public function test_get_customer_by_nonexistent(): void {

		$result = wu_get_customer_by('id', 999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_customer_by_hash returns false for nonexistent.
	 */
	public function test_get_customer_by_hash_nonexistent(): void {

		$result = wu_get_customer_by_hash('nonexistent_hash');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_customers returns array.
	 */
	public function test_get_customers_returns_array(): void {

		$result = wu_get_customers();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_customers with search query.
	 */
	public function test_get_customers_with_search(): void {

		$result = wu_get_customers(['search' => 'nonexistent_user_xyz']);

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_customer_by_user_id returns false for nonexistent.
	 */
	public function test_get_customer_by_user_id_nonexistent(): void {

		$result = wu_get_customer_by_user_id(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_current_customer returns false when no user logged in.
	 */
	public function test_get_current_customer_no_user(): void {

		wp_set_current_user(0);

		$result = wu_get_current_customer();

		$this->assertFalse($result);
	}

	/**
	 * Test wu_create_customer creates a customer.
	 */
	public function test_create_customer(): void {

		$user_id = self::factory()->user->create();

		$customer = wu_create_customer([
			'user_id'         => $user_id,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($customer);
		$this->assertInstanceOf(\WP_Ultimo\Models\Customer::class, $customer);
	}

	/**
	 * Test wu_create_customer with invalid email returns WP_Error.
	 */
	public function test_create_customer_invalid_email(): void {

		$result = wu_create_customer([
			'email'           => 'not-an-email',
			'skip_validation' => true,
		]);

		$this->assertWPError($result);
	}

	/**
	 * Test wu_get_customer retrieves created customer.
	 */
	public function test_get_customer_retrieves_created(): void {

		$user_id = self::factory()->user->create();

		$customer = wu_create_customer([
			'user_id'         => $user_id,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($customer);

		$retrieved = wu_get_customer($customer->get_id());

		$this->assertNotFalse($retrieved);
		$this->assertEquals($customer->get_id(), $retrieved->get_id());
	}

	/**
	 * Test wu_get_customer_by_user_id retrieves created customer.
	 */
	public function test_get_customer_by_user_id_retrieves_created(): void {

		$user_id = self::factory()->user->create();

		$customer = wu_create_customer([
			'user_id'         => $user_id,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($customer);

		$retrieved = wu_get_customer_by_user_id($user_id);

		$this->assertNotFalse($retrieved);
		$this->assertEquals($customer->get_id(), $retrieved->get_id());
	}

	/**
	 * Test wu_username_from_email generates username from email.
	 */
	public function test_username_from_email_basic(): void {

		$username = wu_username_from_email('john.doe@example.com');

		$this->assertNotEmpty($username);
		$this->assertIsString($username);
	}

	/**
	 * Test wu_username_from_email with first and last name.
	 */
	public function test_username_from_email_with_name(): void {

		$username = wu_username_from_email('john@example.com', [
			'first_name' => 'John',
			'last_name'  => 'Doe',
		]);

		$this->assertStringContainsString('john', strtolower($username));
	}

	/**
	 * Test wu_username_from_email with common email prefix falls back to domain.
	 */
	public function test_username_from_email_common_prefix(): void {

		$username = wu_username_from_email('info@example.com');

		// Should use domain part since 'info' is a common prefix
		$this->assertNotEquals('info', $username);
	}

	/**
	 * Test wu_username_from_email with suffix.
	 */
	public function test_username_from_email_with_suffix(): void {

		$username = wu_username_from_email('unique_test_user@example.com', [], '-99');

		$this->assertStringContainsString('-99', $username);
	}

	/**
	 * Test wu_get_customer_meta returns default for nonexistent customer.
	 */
	public function test_get_customer_meta_nonexistent(): void {

		$result = wu_get_customer_meta(999999, 'some_key', 'default_val');

		$this->assertEquals('default_val', $result);
	}

	/**
	 * Test wu_update_customer_meta returns false for nonexistent customer.
	 */
	public function test_update_customer_meta_nonexistent(): void {

		$result = wu_update_customer_meta(999999, 'some_key', 'some_value');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_delete_customer_meta returns false for nonexistent customer.
	 */
	public function test_delete_customer_meta_nonexistent(): void {

		$result = wu_delete_customer_meta(999999, 'some_key');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_customer_gateway_id returns empty string when no memberships.
	 */
	public function test_get_customer_gateway_id_no_memberships(): void {

		$user_id = self::factory()->user->create();

		$customer = wu_create_customer([
			'user_id'         => $user_id,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($customer);

		$result = wu_get_customer_gateway_id($customer->get_id(), ['stripe']);

		$this->assertEquals('', $result);
	}
}
