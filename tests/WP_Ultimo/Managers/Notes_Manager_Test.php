<?php
/**
 * Unit tests for Notes_Manager.
 *
 * @package WP_Ultimo\Tests\Managers
 */

namespace WP_Ultimo\Tests\Managers;

use WP_Ultimo\Managers\Notes_Manager;

/**
 * Unit tests for Notes_Manager.
 */
class Notes_Manager_Test extends \WP_UnitTestCase {

	use Manager_Test_Trait;

	/**
	 * Get the manager class name.
	 *
	 * @return string
	 */
	protected function get_manager_class(): string {
		return Notes_Manager::class;
	}

	/**
	 * Get the expected slug.
	 *
	 * @return string|null
	 */
	protected function get_expected_slug(): ?string {
		return 'notes';
	}

	/**
	 * Get the expected model class.
	 *
	 * @return string|null
	 */
	protected function get_expected_model_class(): ?string {
		return '\\WP_Ultimo\\Models\\Notes';
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {

		$manager = $this->get_manager_instance();

		$manager->init();

		$this->assertIsInt(has_action('plugins_loaded', [$manager, 'register_forms']));
		$this->assertIsInt(has_filter('wu_membership_options_sections', [$manager, 'add_notes_options_section']));
		$this->assertIsInt(has_filter('wu_payments_options_sections', [$manager, 'add_notes_options_section']));
		$this->assertIsInt(has_filter('wu_customer_options_sections', [$manager, 'add_notes_options_section']));
		$this->assertIsInt(has_filter('wu_site_options_sections', [$manager, 'add_notes_options_section']));
	}

	/**
	 * Test register_forms registers expected forms.
	 */
	public function test_register_forms(): void {

		$manager = $this->get_manager_instance();

		$manager->register_forms();

		$form_manager = \WP_Ultimo\Managers\Form_Manager::get_instance();

		$this->assertTrue($form_manager->is_form_registered('add_note'));
		$this->assertTrue($form_manager->is_form_registered('clear_notes'));
		$this->assertTrue($form_manager->is_form_registered('delete_note'));
	}

	/**
	 * Test add_notes_options_section returns sections unchanged without capability.
	 */
	public function test_add_notes_options_section_without_capability(): void {

		$manager = $this->get_manager_instance();

		// Create a subscriber user (no note capabilities)
		$user_id = self::factory()->user->create(['role' => 'subscriber']);
		wp_set_current_user($user_id);

		$sections = ['existing' => ['title' => 'Existing']];

		$result = $manager->add_notes_options_section($sections, new \stdClass());

		// Should return sections unchanged
		$this->assertSame($sections, $result);
	}
}
