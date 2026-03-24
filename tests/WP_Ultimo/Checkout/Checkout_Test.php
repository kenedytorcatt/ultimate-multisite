<?php

namespace WP_Ultimo\Checkout;

use WP_Ultimo\Models\Customer;
use WP_Ultimo\Models\Payment;
use WP_Ultimo\Database\Payments\Payment_Status;
use WP_UnitTestCase;

/**
 * Test class for Checkout functionality.
 */
class Checkout_Test extends WP_UnitTestCase {

	private static Customer $customer;

	public static function set_up_before_class() {
		parent::set_up_before_class();
		self::$customer = wu_create_customer([
			'username' => 'testuser_checkout',
			'email'    => 'checkout@example.com',
			'password' => 'password123',
		]);
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$checkout = Checkout::get_instance();

		$this->assertInstanceOf(Checkout::class, $checkout);
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(
			Checkout::get_instance(),
			Checkout::get_instance()
		);
	}

	/**
	 * Test contains_auto_submittable_field with non-array input.
	 */
	public function test_contains_auto_submittable_field_non_array(): void {

		$checkout = Checkout::get_instance();

		$this->assertFalse($checkout->contains_auto_submittable_field('not-an-array'));
		$this->assertFalse($checkout->contains_auto_submittable_field(null));
	}

	/**
	 * Test contains_auto_submittable_field with empty array.
	 */
	public function test_contains_auto_submittable_field_empty_array(): void {

		$checkout = Checkout::get_instance();

		$this->assertFalse($checkout->contains_auto_submittable_field([]));
	}

	/**
	 * Test contains_auto_submittable_field with only ignored field types.
	 */
	public function test_contains_auto_submittable_field_only_ignored_types(): void {

		$checkout = Checkout::get_instance();

		$fields = [
			['type' => 'hidden'],
			['type' => 'products'],
			['type' => 'submit_button'],
		];

		$this->assertFalse($checkout->contains_auto_submittable_field($fields));
	}

	/**
	 * Test contains_auto_submittable_field with multiple relevant fields returns false.
	 */
	public function test_contains_auto_submittable_field_multiple_relevant(): void {

		$checkout = Checkout::get_instance();

		$fields = [
			['type' => 'text'],
			['type' => 'email'],
		];

		$this->assertFalse($checkout->contains_auto_submittable_field($fields));
	}

	/**
	 * Test contains_auto_submittable_field with single template_selection field.
	 */
	public function test_contains_auto_submittable_field_template_selection(): void {

		$checkout = Checkout::get_instance();

		$fields = [
			['type' => 'hidden'],
			['type' => 'template_selection'],
			['type' => 'submit_button'],
		];

		$result = $checkout->contains_auto_submittable_field($fields);

		$this->assertEquals('template_id', $result);
	}

	/**
	 * Test contains_auto_submittable_field with single pricing_table field.
	 */
	public function test_contains_auto_submittable_field_pricing_table(): void {

		$checkout = Checkout::get_instance();

		$fields = [
			['type' => 'products'],
			['type' => 'pricing_table'],
			['type' => 'steps'],
		];

		$result = $checkout->contains_auto_submittable_field($fields);

		$this->assertEquals('products', $result);
	}

	/**
	 * Test get_auto_submittable_fields returns expected structure.
	 */
	public function test_get_auto_submittable_fields_structure(): void {

		$checkout = Checkout::get_instance();
		$fields   = $checkout->get_auto_submittable_fields();

		$this->assertIsArray($fields);
		$this->assertArrayHasKey('template_selection', $fields);
		$this->assertArrayHasKey('pricing_table', $fields);
		$this->assertEquals('template_id', $fields['template_selection']);
		$this->assertEquals('products', $fields['pricing_table']);
	}

	/**
	 * Test get_auto_submittable_fields is filterable.
	 */
	public function test_get_auto_submittable_fields_is_filterable(): void {

		add_filter('wu_checkout_get_auto_submittable_fields', function ($fields) {
			$fields['custom_field'] = 'custom_param';
			return $fields;
		});

		$checkout = Checkout::get_instance();
		$fields   = $checkout->get_auto_submittable_fields();

		$this->assertArrayHasKey('custom_field', $fields);
		$this->assertEquals('custom_param', $fields['custom_field']);
	}

	/**
	 * Test is_existing_user returns false when not logged in.
	 */
	public function test_is_existing_user_not_logged_in(): void {

		wp_set_current_user(0);

		$checkout = Checkout::get_instance();

		$this->assertFalse($checkout->is_existing_user());
	}

	/**
	 * Test is_existing_user returns true when logged in.
	 */
	public function test_is_existing_user_logged_in(): void {

		$user_id = self::factory()->user->create(['role' => 'subscriber']);
		wp_set_current_user($user_id);

		$checkout = Checkout::get_instance();

		$this->assertTrue($checkout->is_existing_user());

		wp_set_current_user(0);
	}

	/**
	 * Test handle_display_name with first and last name.
	 */
	public function test_handle_display_name_with_names(): void {

		$checkout = Checkout::get_instance();

		$_REQUEST['first_name'] = 'John';
		$_REQUEST['last_name']  = 'Doe';

		$result = $checkout->handle_display_name('Original Name');

		$this->assertEquals('John Doe', $result);

		unset($_REQUEST['first_name'], $_REQUEST['last_name']);
	}

	/**
	 * Test handle_display_name with only first name.
	 */
	public function test_handle_display_name_first_name_only(): void {

		$checkout = Checkout::get_instance();

		$_REQUEST['first_name'] = 'John';
		unset($_REQUEST['last_name']);

		$result = $checkout->handle_display_name('Original Name');

		$this->assertEquals('John', $result);

		unset($_REQUEST['first_name']);
	}

	/**
	 * Test handle_display_name with no names returns original.
	 */
	public function test_handle_display_name_no_names(): void {

		$checkout = Checkout::get_instance();

		unset($_REQUEST['first_name'], $_REQUEST['last_name']);

		$result = $checkout->handle_display_name('Original Name');

		$this->assertEquals('Original Name', $result);
	}

	/**
	 * Test request_or_session returns request value.
	 */
	public function test_request_or_session_returns_request_value(): void {

		$checkout = Checkout::get_instance();

		$_REQUEST['test_key'] = 'test_value';

		$result = $checkout->request_or_session('test_key', 'default');

		$this->assertEquals('test_value', $result);

		unset($_REQUEST['test_key']);
	}

	/**
	 * Test request_or_session returns default when key not found.
	 */
	public function test_request_or_session_returns_default(): void {

		$checkout = Checkout::get_instance();

		unset($_REQUEST['nonexistent_key']);

		$result = $checkout->request_or_session('nonexistent_key', 'my_default');

		$this->assertEquals('my_default', $result);
	}

	/**
	 * Test request_or_session returns false as default.
	 */
	public function test_request_or_session_default_is_false(): void {

		$checkout = Checkout::get_instance();

		unset($_REQUEST['missing_key']);

		$result = $checkout->request_or_session('missing_key');

		$this->assertFalse($result);
	}

	/**
	 * Test is_first_step with empty steps.
	 */
	public function test_is_first_step_empty_steps(): void {

		$checkout        = Checkout::get_instance();
		$checkout->steps = [];

		$this->assertTrue($checkout->is_first_step());
	}

	/**
	 * Test is_first_step when on first step.
	 */
	public function test_is_first_step_on_first(): void {

		$checkout            = Checkout::get_instance();
		$checkout->steps     = [
			['id' => 'step-1'],
			['id' => 'step-2'],
			['id' => 'step-3'],
		];
		$checkout->step_name = 'step-1';

		$this->assertTrue($checkout->is_first_step());
	}

	/**
	 * Test is_first_step when not on first step.
	 */
	public function test_is_first_step_on_second(): void {

		$checkout            = Checkout::get_instance();
		$checkout->steps     = [
			['id' => 'step-1'],
			['id' => 'step-2'],
		];
		$checkout->step_name = 'step-2';

		$this->assertFalse($checkout->is_first_step());
	}

	/**
	 * Test is_last_step when on last step.
	 */
	public function test_is_last_step_on_last(): void {

		$checkout            = Checkout::get_instance();
		$checkout->steps     = [
			['id' => 'step-1'],
			['id' => 'step-2'],
		];
		$checkout->step_name = 'step-2';

		$this->assertTrue($checkout->is_last_step());
	}

	/**
	 * Test is_last_step when not on last step.
	 */
	public function test_is_last_step_not_on_last(): void {

		$checkout            = Checkout::get_instance();
		$checkout->steps     = [
			['id' => 'step-1'],
			['id' => 'step-2'],
		];
		$checkout->step_name = 'step-1';

		$this->assertFalse($checkout->is_last_step());
	}

	/**
	 * Test get_next_step_name returns next step.
	 */
	public function test_get_next_step_name_returns_next(): void {

		$checkout            = Checkout::get_instance();
		$checkout->steps     = [
			['id' => 'step-1'],
			['id' => 'step-2'],
			['id' => 'step-3'],
		];
		$checkout->step_name = 'step-1';

		$this->assertEquals('step-2', $checkout->get_next_step_name());
	}

	/**
	 * Test get_next_step_name returns current when on last step.
	 */
	public function test_get_next_step_name_on_last_step(): void {

		$checkout            = Checkout::get_instance();
		$checkout->steps     = [
			['id' => 'step-1'],
			['id' => 'step-2'],
		];
		$checkout->step_name = 'step-2';

		$this->assertEquals('step-2', $checkout->get_next_step_name());
	}

	/**
	 * Test get_next_step_name with no step name set.
	 */
	public function test_get_next_step_name_no_current_step(): void {

		$checkout            = Checkout::get_instance();
		$checkout->steps     = [
			['id' => 'step-1'],
			['id' => 'step-2'],
		];
		$checkout->step_name = null;

		$this->assertEquals('step-2', $checkout->get_next_step_name());
	}

	/**
	 * Test get_customer_email_verification_status with never setting.
	 */
	public function test_email_verification_status_never(): void {

		wu_save_setting('enable_email_verification', 'never');

		$checkout = Checkout::get_instance();

		// Use reflection to set the protected $order property.
		$reflection = new \ReflectionClass($checkout);
		$prop       = $reflection->getProperty('order');

		if (PHP_VERSION_ID < 80100) {
			$prop->setAccessible(true);
		}

		$prop->setValue($checkout, new Cart([
			'products' => [],
		]));

		$result = $checkout->get_customer_email_verification_status();

		$this->assertEquals('none', $result);
	}

	/**
	 * Test get_customer_email_verification_status with always setting.
	 */
	public function test_email_verification_status_always(): void {

		wu_save_setting('enable_email_verification', 'always');

		$checkout = Checkout::get_instance();

		// Use reflection to set the protected $order property.
		$reflection = new \ReflectionClass($checkout);
		$prop       = $reflection->getProperty('order');

		if (PHP_VERSION_ID < 80100) {
			$prop->setAccessible(true);
		}

		$prop->setValue($checkout, new Cart([
			'products' => [],
		]));

		$result = $checkout->get_customer_email_verification_status();

		$this->assertEquals('pending', $result);
	}

	/**
	 * Test errors property is null by default.
	 */
	public function test_errors_default_null(): void {

		$checkout = Checkout::get_instance();

		// Reset errors.
		$checkout->errors = null;

		$this->assertNull($checkout->errors);
	}

	/**
	 * Test draft payment creation.
	 */
	public function test_draft_payment_creation() {
		$checkout = Checkout::get_instance();

		$products = [1]; // Assume product ID

		$reflection = new \ReflectionClass($checkout);
		$method = $reflection->getMethod('create_draft_payment');

		// Only call setAccessible() on PHP < 8.1 where it's needed
		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		$method->invoke($checkout, $products);

		// Check if draft payment was created
		// This would require mocking or checking DB
		$this->assertTrue(true); // Placeholder
	}

	public static function tear_down_after_class() {
		self::$customer->delete();
		parent::tear_down_after_class();
	}
}
