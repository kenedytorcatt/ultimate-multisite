<?php
/**
 * Test case for Legacy_Checkout.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 */

namespace WP_Ultimo\Tests\Checkout;

use WP_Ultimo\Checkout\Legacy_Checkout;

/**
 * Test Legacy_Checkout functionality.
 */
class Legacy_Checkout_Test extends \WP_UnitTestCase {

	/**
	 * The Legacy_Checkout instance.
	 *
	 * @var Legacy_Checkout
	 */
	private $checkout;

	/**
	 * Set up test.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->checkout = Legacy_Checkout::get_instance();
	}

	/**
	 * Test singleton instance.
	 */
	public function test_singleton_instance(): void {

		$this->assertInstanceOf(Legacy_Checkout::class, $this->checkout);
		$this->assertSame($this->checkout, Legacy_Checkout::get_instance());
	}

	/**
	 * Test is_customizer returns false by default.
	 */
	public function test_is_customizer_returns_false_by_default(): void {

		$result = Legacy_Checkout::is_customizer();

		$this->assertFalse($result);
	}

	/**
	 * Test is_customizer returns true when customizer param is set.
	 */
	public function test_is_customizer_returns_true_with_param(): void {

		$_GET['wu-signup-customizer-preview'] = '1';

		$result = Legacy_Checkout::is_customizer();

		$this->assertTrue($result);

		unset($_GET['wu-signup-customizer-preview']);
	}

	/**
	 * Test get_steps returns array with expected keys.
	 */
	public function test_get_steps_returns_array(): void {

		$steps = $this->checkout->get_steps(true, false);

		$this->assertIsArray($steps);
		$this->assertNotEmpty($steps);
	}

	/**
	 * Test get_steps includes begin-signup and create-account.
	 */
	public function test_get_steps_includes_hidden_steps(): void {

		$steps = $this->checkout->get_steps(true, false);

		$this->assertArrayHasKey('begin-signup', $steps);
		$this->assertArrayHasKey('create-account', $steps);
	}

	/**
	 * Test get_steps excludes hidden steps when requested.
	 */
	public function test_get_steps_excludes_hidden_steps(): void {

		$steps = $this->checkout->get_steps(false, false);

		$this->assertArrayNotHasKey('begin-signup', $steps);
		$this->assertArrayNotHasKey('create-account', $steps);
	}

	/**
	 * Test get_steps includes domain step.
	 */
	public function test_get_steps_includes_domain_step(): void {

		$steps = $this->checkout->get_steps(true, false);

		$this->assertArrayHasKey('domain', $steps);
	}

	/**
	 * Test get_steps includes account step.
	 */
	public function test_get_steps_includes_account_step(): void {

		$steps = $this->checkout->get_steps(true, false);

		$this->assertArrayHasKey('account', $steps);
	}

	/**
	 * Test get_steps includes template step.
	 */
	public function test_get_steps_includes_template_step(): void {

		$steps = $this->checkout->get_steps(true, false);

		$this->assertArrayHasKey('template', $steps);
	}

	/**
	 * Test steps have required properties.
	 */
	public function test_steps_have_required_properties(): void {

		$steps = $this->checkout->get_steps(true, false);

		foreach ($steps as $step_id => $step) {
			$this->assertArrayHasKey('name', $step, "Step '$step_id' missing 'name' key");
		}
	}

	/**
	 * Test sort_steps_and_fields sorts correctly.
	 */
	public function test_sort_steps_and_fields(): void {

		$a = ['order' => 10];
		$b = ['order' => 20];

		$result = $this->checkout->sort_steps_and_fields($a, $b);

		$this->assertLessThan(0, $result);
	}

	/**
	 * Test sort_steps_and_fields with equal order.
	 */
	public function test_sort_steps_and_fields_equal_order(): void {

		$a = ['order' => 10];
		$b = ['order' => 10];

		$result = $this->checkout->sort_steps_and_fields($a, $b);

		$this->assertEquals(0, $result);
	}

	/**
	 * Test sort_steps_and_fields with missing order defaults to 50.
	 */
	public function test_sort_steps_and_fields_missing_order(): void {

		$a = [];
		$b = ['order' => 50];

		$result = $this->checkout->sort_steps_and_fields($a, $b);

		$this->assertEquals(0, $result);
	}

	/**
	 * Test get_transient returns array.
	 */
	public function test_get_transient_returns_array(): void {

		$result = Legacy_Checkout::get_transient(false);

		$this->assertIsArray($result);
	}

	/**
	 * Test get_transient in customizer mode.
	 */
	public function test_get_transient_in_customizer_mode(): void {

		$_GET['wu-signup-customizer-preview'] = '1';

		$result = Legacy_Checkout::get_transient(false);

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);

		unset($_GET['wu-signup-customizer-preview']);
	}

	/**
	 * Test has_plan_step returns boolean.
	 */
	public function test_has_plan_step_returns_boolean(): void {

		$result = $this->checkout->has_plan_step();

		$this->assertIsBool($result);
	}

	/**
	 * Test add_new_template merges templates on main site.
	 */
	public function test_add_new_template(): void {

		$existing = ['template-a.php' => 'Template A'];

		$result = $this->checkout->add_new_template($existing);

		$this->assertIsArray($result);
		// On main site, should have merged the legacy template
		if (is_main_site()) {
			$this->assertArrayHasKey('signup-main.php', $result);
		}
		// Original template should still be there
		$this->assertArrayHasKey('template-a.php', $result);
	}

	/**
	 * Test register_legacy_templates returns atts unchanged.
	 */
	public function test_register_legacy_templates_returns_atts(): void {

		$atts = ['post_title' => 'Test'];

		$result = $this->checkout->register_legacy_templates($atts);

		$this->assertEquals($atts, $result);
	}

	/**
	 * Test view_legacy_template returns template for search.
	 */
	public function test_view_legacy_template_returns_template_for_search(): void {

		// Simulate a search query
		$this->go_to('/?s=test');

		$template = '/path/to/template.php';

		$result = $this->checkout->view_legacy_template($template);

		$this->assertEquals($template, $result);
	}

	/**
	 * Test view_legacy_template returns template when no post.
	 */
	public function test_view_legacy_template_no_post(): void {

		global $post;
		$original_post = $post;
		$post          = null;

		$template = '/path/to/template.php';

		$result = $this->checkout->view_legacy_template($template);

		$this->assertEquals($template, $result);

		$post = $original_post;
	}

	/**
	 * Test get_legacy_dynamic_styles returns CSS string.
	 */
	public function test_get_legacy_dynamic_styles(): void {

		$styles = $this->checkout->get_legacy_dynamic_styles();

		$this->assertIsString($styles);
		$this->assertStringContainsString('plan-tier', $styles);
	}

	/**
	 * Test get_site_url_for_previewer returns string.
	 */
	public function test_get_site_url_for_previewer(): void {

		$url = $this->checkout->get_site_url_for_previewer();

		$this->assertIsString($url);
		$this->assertNotEmpty($url);
	}

	/**
	 * Test add_signup_step adds step via filter.
	 */
	public function test_add_signup_step(): void {

		$this->checkout->add_signup_step('custom-step', 25, [
			'name' => 'Custom Step',
			'view' => false,
		]);

		$steps = $this->checkout->get_steps(true, true);

		$this->assertArrayHasKey('custom-step', $steps);
		$this->assertEquals('Custom Step', $steps['custom-step']['name']);
		$this->assertEquals(25, $steps['custom-step']['order']);
		$this->assertFalse($steps['custom-step']['core']);
	}

	/**
	 * Test add_signup_field adds field to step.
	 */
	public function test_add_signup_field(): void {

		$this->checkout->add_signup_field('account', 'custom_field', 55, [
			'name' => 'Custom Field',
			'type' => 'text',
		]);

		$steps = $this->checkout->get_steps(true, true);

		$this->assertArrayHasKey('account', $steps);
		$this->assertArrayHasKey('custom_field', $steps['account']['fields']);
		$this->assertEquals('Custom Field', $steps['account']['fields']['custom_field']['name']);
	}
}
