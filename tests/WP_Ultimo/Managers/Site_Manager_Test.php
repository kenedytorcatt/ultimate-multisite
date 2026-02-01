<?php
/**
 * Unit tests for Site_Manager.
 *
 * @package WP_Ultimo\Tests\Managers
 */

namespace WP_Ultimo\Tests\Managers;

use WP_Ultimo\Managers\Site_Manager;

class Site_Manager_Test extends \WP_UnitTestCase {

	use Manager_Test_Trait;

	protected function get_manager_class(): string {
		return Site_Manager::class;
	}

	protected function get_expected_slug(): ?string {
		return 'site';
	}

	protected function get_expected_model_class(): ?string {
		return \WP_Ultimo\Models\Site::class;
	}

	/**
	 * Test allow_hyphens_in_site_name removes the WP error and allows hyphens.
	 */
	public function test_allow_hyphens_allows_valid_hyphenated_name(): void {

		$manager = $this->get_manager_instance();

		$errors = new \WP_Error();
		$errors->add('blogname', __('Site names can only contain lowercase letters (a-z) and numbers.', 'ultimate-multisite'));

		$result = $manager->allow_hyphens_in_site_name(
			[
				'blogname' => 'my-site',
				'errors'   => $errors,
			]
		);

		$this->assertFalse(
			$result['errors']->has_errors(),
			'Hyphenated site name should be valid.'
		);
	}

	/**
	 * Test allow_hyphens_in_site_name rejects invalid characters.
	 */
	public function test_allow_hyphens_rejects_invalid_chars(): void {

		$manager = $this->get_manager_instance();

		$errors = new \WP_Error();
		$errors->add('blogname', __('Site names can only contain lowercase letters (a-z) and numbers.', 'ultimate-multisite'));

		$result = $manager->allow_hyphens_in_site_name(
			[
				'blogname' => 'my_site!',
				'errors'   => $errors,
			]
		);

		$this->assertTrue(
			$result['errors']->has_errors(),
			'Site name with underscores and special chars should be invalid.'
		);
	}

	/**
	 * Test filter_illegal_search_keys removes null/false/empty keys.
	 */
	public function test_filter_illegal_search_keys(): void {

		$manager = $this->get_manager_instance();

		$input = [
			'good_key'  => 'value1',
			''          => 'value2',
			'another'   => 'value3',
			false       => 'value4',
		];

		$result = $manager->filter_illegal_search_keys($input);

		$this->assertArrayHasKey('good_key', $result);
		$this->assertArrayHasKey('another', $result);
		$this->assertCount(2, $result);
	}

	/**
	 * Test get_search_and_replace_settings returns pairs from settings.
	 */
	public function test_get_search_and_replace_settings(): void {

		wu_save_setting(
			'search_and_replace',
			[
				['search' => 'foo', 'replace' => 'bar'],
				['search' => '', 'replace' => 'baz'],
				['search' => 'hello', 'replace' => 'world'],
			]
		);

		$manager = $this->get_manager_instance();
		$pairs   = $manager->get_search_and_replace_settings();

		$this->assertIsArray($pairs);
		$this->assertEquals('bar', $pairs['foo']);
		$this->assertEquals('world', $pairs['hello']);
		$this->assertArrayNotHasKey('', $pairs, 'Empty search keys should be excluded.');
	}

	/**
	 * Test login_header_url returns the site URL.
	 */
	public function test_login_header_url(): void {

		$manager = $this->get_manager_instance();

		$this->assertEquals(get_site_url(), $manager->login_header_url());
	}

	/**
	 * Test login_header_text returns the blog name.
	 */
	public function test_login_header_text(): void {

		$manager = $this->get_manager_instance();

		$this->assertEquals(get_bloginfo('name'), $manager->login_header_text());
	}

	/**
	 * Test hide_super_admin_from_list adds exclusion for non-super-admins.
	 */
	public function test_hide_super_admin_from_list(): void {

		$manager = $this->get_manager_instance();

		// As a non-super admin user.
		$user_id = $this->factory()->user->create(['role' => 'administrator']);
		wp_set_current_user($user_id);

		$args   = [];
		$result = $manager->hide_super_admin_from_list($args);

		$this->assertArrayHasKey('login__not_in', $result);

		// Restore.
		wp_set_current_user(0);
	}
}
