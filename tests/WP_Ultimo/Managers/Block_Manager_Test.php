<?php
/**
 * Unit tests for Block_Manager.
 *
 * @package WP_Ultimo\Tests\Managers
 */

namespace WP_Ultimo\Tests\Managers;

use WP_Ultimo\Managers\Block_Manager;

/**
 * Unit tests for Block_Manager.
 */
class Block_Manager_Test extends \WP_UnitTestCase {

	use Manager_Test_Trait;

	/**
	 * Get the manager class name.
	 *
	 * @return string
	 */
	protected function get_manager_class(): string {
		return Block_Manager::class;
	}

	/**
	 * Get the expected slug.
	 *
	 * @return string|null
	 */
	protected function get_expected_slug(): ?string {
		return null;
	}

	/**
	 * Get the expected model class.
	 *
	 * @return string|null
	 */
	protected function get_expected_model_class(): ?string {
		return null;
	}

	/**
	 * Test that add_wp_ultimo_block_category adds the category.
	 */
	public function test_add_wp_ultimo_block_category(): void {

		$manager    = $this->get_manager_instance();
		$categories = $manager->add_wp_ultimo_block_category([], null);

		$this->assertIsArray($categories);
		$this->assertNotEmpty($categories);

		$slugs = array_column($categories, 'slug');
		$this->assertContains('wp-ultimo', $slugs);
	}

	/**
	 * Test that block category preserves existing categories.
	 */
	public function test_block_category_preserves_existing(): void {

		$manager  = $this->get_manager_instance();
		$existing = [
			[
				'slug'  => 'common',
				'title' => 'Common',
			],
			[
				'slug'  => 'formatting',
				'title' => 'Formatting',
			],
		];

		$categories = $manager->add_wp_ultimo_block_category($existing, null);

		$this->assertCount(3, $categories);

		$slugs = array_column($categories, 'slug');
		$this->assertContains('common', $slugs);
		$this->assertContains('formatting', $slugs);
		$this->assertContains('wp-ultimo', $slugs);
	}

	/**
	 * Test that block category has correct title.
	 */
	public function test_block_category_has_correct_title(): void {

		$manager    = $this->get_manager_instance();
		$categories = $manager->add_wp_ultimo_block_category([], null);

		$wu_category = null;

		foreach ($categories as $cat) {
			if ($cat['slug'] === 'wp-ultimo') {
				$wu_category = $cat;
				break;
			}
		}

		$this->assertNotNull($wu_category);
		$this->assertArrayHasKey('title', $wu_category);
		$this->assertNotEmpty($wu_category['title']);
	}

	/**
	 * Test that init registers the filter hook.
	 */
	public function test_init_registers_filter(): void {

		$manager = $this->get_manager_instance();
		$manager->init();

		// Check that the filter is registered on one of the block category hooks
		global $wp_version;

		$hook = version_compare($wp_version, '5.8', '<') ? 'block_categories' : 'block_categories_all';

		$this->assertNotFalse(has_filter($hook, [$manager, 'add_wp_ultimo_block_category']));
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$instance1 = Block_Manager::get_instance();
		$instance2 = Block_Manager::get_instance();

		$this->assertSame($instance1, $instance2);
	}

	/**
	 * Test block category with empty categories array.
	 */
	public function test_block_category_with_empty_array(): void {

		$manager    = $this->get_manager_instance();
		$categories = $manager->add_wp_ultimo_block_category([], null);

		$this->assertCount(1, $categories);
		$this->assertEquals('wp-ultimo', $categories[0]['slug']);
	}
}
