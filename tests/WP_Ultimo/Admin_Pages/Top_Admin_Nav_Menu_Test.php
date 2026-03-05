<?php
/**
 * Unit tests for Top_Admin_Nav_Menu.
 *
 * @package WP_Ultimo\Tests\Admin_Pages
 */

namespace WP_Ultimo\Tests\Admin_Pages;

use WP_Ultimo\Admin_Pages\Top_Admin_Nav_Menu;
use WP_Ultimo\Settings;

class Top_Admin_Nav_Menu_Test extends \WP_UnitTestCase {

	/**
	 * Top Admin Nav Menu instance.
	 *
	 * @var Top_Admin_Nav_Menu
	 */
	protected $menu;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {

		parent::setUp();

		$this->menu = new Top_Admin_Nav_Menu();
	}

	/**
	 * Test constructor registers hooks.
	 */
	public function test_constructor_registers_hooks(): void {

		$menu = new Top_Admin_Nav_Menu();

		$priority = has_action('admin_bar_menu', [$menu, 'add_top_bar_menus']);

		$this->assertNotFalse($priority);
		$this->assertEquals(50, $priority);
	}

	/**
	 * Test add_top_bar_menus does nothing for non-super-admin.
	 */
	public function test_add_top_bar_menus_returns_early_for_non_super_admin(): void {

		$user_id = $this->factory()->user->create();
		wp_set_current_user($user_id);

		// Mock WP_Admin_Bar
		$admin_bar = $this->getMockBuilder('WP_Admin_Bar')
			->disableOriginalConstructor()
			->getMock();

		// Should not call add_node since user is not super admin
		$admin_bar->expects($this->never())
			->method('add_node');

		$this->menu->add_top_bar_menus($admin_bar);
	}

	/**
	 * Test add_top_bar_menus adds parent node for super admin.
	 */
	public function test_add_top_bar_menus_adds_parent_node_for_super_admin(): void {

		$user_id = $this->factory()->user->create();
		grant_super_admin($user_id);
		wp_set_current_user($user_id);

		// Mock WP_Admin_Bar
		$admin_bar = $this->getMockBuilder('WP_Admin_Bar')
			->disableOriginalConstructor()
			->getMock();

		// Expect parent node to be added
		$admin_bar->expects($this->atLeastOnce())
			->method('add_node')
			->with($this->callback(function ($node) {
				return isset($node['id']) && $node['id'] === 'wp-ultimo';
			}));

		$this->menu->add_top_bar_menus($admin_bar);
	}

	/**
	 * Test add_top_bar_menus adds sites node when user has capability.
	 */
	public function test_add_top_bar_menus_adds_sites_node_with_capability(): void {

		$user_id = $this->factory()->user->create(['role' => 'administrator']);
		grant_super_admin($user_id);
		wp_set_current_user($user_id);

		// Grant capability
		$user = wp_get_current_user();
		$user->add_cap('wu_read_sites');

		$admin_bar = $this->getMockBuilder('WP_Admin_Bar')
			->disableOriginalConstructor()
			->getMock();

		// Track all nodes added
		$nodes_added = [];
		$admin_bar->method('add_node')
			->willReturnCallback(function ($node) use (&$nodes_added) {
				$nodes_added[] = $node['id'];
			});

		$this->menu->add_top_bar_menus($admin_bar);

		// Should include sites node
		$this->assertContains('wp-ultimo-sites', $nodes_added);

		$user->remove_cap('wu_read_sites');
	}

	/**
	 * Test add_top_bar_menus uses lightweight section names.
	 */
	public function test_add_top_bar_menus_uses_lightweight_section_names(): void {

		$user_id = $this->factory()->user->create(['role' => 'administrator']);
		grant_super_admin($user_id);
		wp_set_current_user($user_id);

		// Grant settings capability
		$user = wp_get_current_user();
		$user->add_cap('wu_read_settings');

		$admin_bar = $this->getMockBuilder('WP_Admin_Bar')
			->disableOriginalConstructor()
			->getMock();

		$nodes_added = [];
		$admin_bar->method('add_node')
			->willReturnCallback(function ($node) use (&$nodes_added) {
				$nodes_added[] = $node['id'];
			});

		$this->menu->add_top_bar_menus($admin_bar);

		// Should have settings menu items
		$this->assertContains('wp-ultimo-settings-group', $nodes_added);
		$this->assertContains('wp-ultimo-settings', $nodes_added);

		$user->remove_cap('wu_read_settings');
	}

	/**
	 * Test menu respects capabilities for each section.
	 */
	public function test_menu_respects_capabilities(): void {

		$user_id = $this->factory()->user->create();
		grant_super_admin($user_id);
		wp_set_current_user($user_id);

		// User has no specific capabilities except super admin
		$admin_bar = $this->getMockBuilder('WP_Admin_Bar')
			->disableOriginalConstructor()
			->getMock();

		$nodes_added = [];
		$admin_bar->method('add_node')
			->willReturnCallback(function ($node) use (&$nodes_added) {
				$nodes_added[] = $node['id'];
			});

		$this->menu->add_top_bar_menus($admin_bar);

		// Parent should always be added for super admin
		$this->assertContains('wp-ultimo', $nodes_added);
	}

	/**
	 * Test addon tabs are grouped separately.
	 */
	public function test_addon_tabs_grouped_separately(): void {

		$user_id = $this->factory()->user->create(['role' => 'administrator']);
		grant_super_admin($user_id);
		wp_set_current_user($user_id);

		$user = wp_get_current_user();
		$user->add_cap('wu_read_settings');

		// Add a mock addon tab via filter
		add_filter('wu_settings_section_names', function ($sections) {
			$sections['test_addon'] = [
				'title' => 'Test Addon',
				'icon'  => 'dashicons-wu-test',
				'addon' => true,
			];
			return $sections;
		});

		$admin_bar = $this->getMockBuilder('WP_Admin_Bar')
			->disableOriginalConstructor()
			->getMock();

		$nodes_added = [];
		$admin_bar->method('add_node')
			->willReturnCallback(function ($node) use (&$nodes_added) {
				$nodes_added[] = $node['id'];
			});

		$this->menu->add_top_bar_menus($admin_bar);

		// Should have addon settings group
		$this->assertContains('wp-ultimo-settings-addons', $nodes_added);
		$this->assertContains('wp-ultimo-settings-test_addon', $nodes_added);

		$user->remove_cap('wu_read_settings');
	}

	/**
	 * Test menu skips invisible sections.
	 */
	public function test_menu_skips_invisible_sections(): void {

		$user_id = $this->factory()->user->create(['role' => 'administrator']);
		grant_super_admin($user_id);
		wp_set_current_user($user_id);

		$user = wp_get_current_user();
		$user->add_cap('wu_read_settings');

		// Add an invisible section
		add_filter('wu_settings_section_names', function ($sections) {
			$sections['invisible_section'] = [
				'title'     => 'Invisible',
				'icon'      => 'dashicons-wu-test',
				'invisible' => true,
			];
			return $sections;
		}, 20);

		$admin_bar = $this->getMockBuilder('WP_Admin_Bar')
			->disableOriginalConstructor()
			->getMock();

		$nodes_added = [];
		$admin_bar->method('add_node')
			->willReturnCallback(function ($node) use (&$nodes_added) {
				$nodes_added[] = $node['id'];
			});

		$this->menu->add_top_bar_menus($admin_bar);

		// Should NOT include invisible section
		$this->assertNotContains('wp-ultimo-settings-invisible_section', $nodes_added);

		$user->remove_cap('wu_read_settings');
	}

	/**
	 * Test boundary: User with no capabilities sees only parent.
	 */
	public function test_user_with_no_specific_caps_sees_parent_only(): void {

		$user_id = $this->factory()->user->create();
		grant_super_admin($user_id);
		wp_set_current_user($user_id);

		$admin_bar = $this->getMockBuilder('WP_Admin_Bar')
			->disableOriginalConstructor()
			->getMock();

		$nodes_added = [];
		$admin_bar->method('add_node')
			->willReturnCallback(function ($node) use (&$nodes_added) {
				$nodes_added[] = $node['id'];
			});

		$this->menu->add_top_bar_menus($admin_bar);

		// Only parent should be present
		$this->assertContains('wp-ultimo', $nodes_added);

		// Specific menus should not be added
		$this->assertNotContains('wp-ultimo-sites', $nodes_added);
		$this->assertNotContains('wp-ultimo-customers', $nodes_added);
	}
}