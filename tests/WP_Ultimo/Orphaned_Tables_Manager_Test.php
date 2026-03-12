<?php
/**
 * Unit tests for Orphaned_Tables_Manager.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

class Orphaned_Tables_Manager_Test extends \WP_UnitTestCase {

	/**
	 * Get the singleton instance.
	 *
	 * @return Orphaned_Tables_Manager
	 */
	protected function get_instance(): Orphaned_Tables_Manager {

		return Orphaned_Tables_Manager::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(Orphaned_Tables_Manager::class, $this->get_instance());
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(
			Orphaned_Tables_Manager::get_instance(),
			Orphaned_Tables_Manager::get_instance()
		);
	}

	/**
	 * Test init registers hooks when WP version is 6.2+.
	 */
	public function test_init_registers_hooks(): void {

		global $wp_version;

		// Current WP should be >= 6.2
		if (version_compare($wp_version, '6.2', '<')) {
			$this->markTestSkipped('Requires WordPress 6.2+');
		}

		$instance = $this->get_instance();

		$instance->init();

		$this->assertIsInt(has_action('plugins_loaded', [$instance, 'register_forms']));
		$this->assertIsInt(has_action('wu_settings_other', [$instance, 'register_settings_field']));
	}

	/**
	 * Test find_orphaned_tables returns array.
	 */
	public function test_find_orphaned_tables_returns_array(): void {

		$result = $this->get_instance()->find_orphaned_tables();

		$this->assertIsArray($result);
	}

	/**
	 * Test find_orphaned_tables does not include active site tables.
	 */
	public function test_find_orphaned_tables_excludes_active_sites(): void {

		$orphaned = $this->get_instance()->find_orphaned_tables();

		// Get active site IDs
		$site_ids = get_sites([
			'fields' => 'ids',
			'number' => 0,
		]);

		global $wpdb;

		foreach ($orphaned as $table) {
			$pattern = '/^' . preg_quote($wpdb->prefix, '/') . '([0-9]+)_/';

			if (preg_match($pattern, $table, $matches)) {
				$site_id = (int) $matches[1];

				// Orphaned tables should NOT belong to active sites
				$this->assertNotContains($site_id, $site_ids, "Table {$table} belongs to active site {$site_id}");
			}
		}
	}

	/**
	 * Test delete_orphaned_tables returns int.
	 */
	public function test_delete_orphaned_tables_returns_int(): void {

		$result = $this->get_instance()->delete_orphaned_tables([]);

		$this->assertSame(0, $result);
	}

	/**
	 * Test delete_orphaned_tables rejects non-matching table names.
	 */
	public function test_delete_orphaned_tables_rejects_invalid_names(): void {

		$result = $this->get_instance()->delete_orphaned_tables([
			'some_random_table',
			'another_table',
		]);

		// Should not delete tables that don't match the multisite pattern
		$this->assertSame(0, $result);
	}

	/**
	 * Test register_forms registers the orphaned_tables_delete form.
	 */
	public function test_register_forms(): void {

		$instance = $this->get_instance();

		$instance->register_forms();

		$form_manager = \WP_Ultimo\Managers\Form_Manager::get_instance();

		$this->assertTrue($form_manager->is_form_registered('orphaned_tables_delete'));
	}
}
