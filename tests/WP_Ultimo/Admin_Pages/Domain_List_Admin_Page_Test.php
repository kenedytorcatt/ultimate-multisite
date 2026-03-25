<?php
/**
 * Tests for Domain_List_Admin_Page class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Admin_Pages;

use WP_UnitTestCase;

/**
 * Test class for Domain_List_Admin_Page.
 */
class Domain_List_Admin_Page_Test extends WP_UnitTestCase {

	/**
	 * @var Domain_List_Admin_Page
	 */
	private $page;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->page = new Domain_List_Admin_Page();
	}

	/**
	 * Test page id is correct.
	 */
	public function test_page_id(): void {
		$reflection = new \ReflectionClass($this->page);
		$property   = $reflection->getProperty('id');
		$property->setAccessible(true);

		$this->assertEquals('wp-ultimo-domains', $property->getValue($this->page));
	}

	/**
	 * Test page type is submenu.
	 */
	public function test_page_type(): void {
		$reflection = new \ReflectionClass($this->page);
		$property   = $reflection->getProperty('type');
		$property->setAccessible(true);

		$this->assertEquals('submenu', $property->getValue($this->page));
	}

	/**
	 * Test get_title returns string.
	 */
	public function test_get_title(): void {
		$title = $this->page->get_title();

		$this->assertIsString($title);
		$this->assertEquals('Domains', $title);
	}

	/**
	 * Test get_menu_title returns string.
	 */
	public function test_get_menu_title(): void {
		$title = $this->page->get_menu_title();

		$this->assertIsString($title);
		$this->assertEquals('Domains', $title);
	}

	/**
	 * Test get_submenu_title returns string.
	 */
	public function test_get_submenu_title(): void {
		$title = $this->page->get_submenu_title();

		$this->assertIsString($title);
		$this->assertEquals('Domains', $title);
	}

	/**
	 * Test get_labels returns array.
	 */
	public function test_get_labels(): void {
		$labels = $this->page->get_labels();

		$this->assertIsArray($labels);
		$this->assertArrayHasKey('deleted_message', $labels);
		$this->assertArrayHasKey('search_label', $labels);
	}

	/**
	 * Test action_links returns array.
	 */
	public function test_action_links(): void {
		$links = $this->page->action_links();

		$this->assertIsArray($links);
		$this->assertCount(1, $links);
	}

	/**
	 * Test action_links has add domain link.
	 */
	public function test_action_links_add_domain(): void {
		$links = $this->page->action_links();

		$this->assertEquals('Add Domain', $links[0]['label']);
		$this->assertArrayHasKey('url', $links[0]);
		$this->assertArrayHasKey('icon', $links[0]);
	}

	/**
	 * Test table returns list table instance.
	 */
	public function test_table(): void {
		$table = $this->page->table();

		$this->assertInstanceOf(\WP_Ultimo\List_Tables\Domain_List_Table::class, $table);
	}

	/**
	 * Test supported_panels contains network_admin_menu.
	 */
	public function test_supported_panels(): void {
		$reflection = new \ReflectionClass($this->page);
		$property   = $reflection->getProperty('supported_panels');
		$property->setAccessible(true);

		$panels = $property->getValue($this->page);
		$this->assertArrayHasKey('network_admin_menu', $panels);
		$this->assertEquals('wu_read_domains', $panels['network_admin_menu']);
	}

	/**
	 * Test badge_count is zero.
	 */
	public function test_badge_count(): void {
		$reflection = new \ReflectionClass($this->page);
		$property   = $reflection->getProperty('badge_count');
		$property->setAccessible(true);

		$this->assertEquals(0, $property->getValue($this->page));
	}
}
