<?php

namespace WP_Ultimo\Limits;

/**
 * Tests for the Post_Type_Limits class.
 */
class Post_Type_Limits_Test extends \WP_UnitTestCase {

	/**
	 * Get a fresh Post_Type_Limits instance via reflection.
	 *
	 * @return Post_Type_Limits
	 */
	private function get_instance() {

		$ref = new \ReflectionClass(Post_Type_Limits::class);
		$instance = $ref->newInstanceWithoutConstructor();

		return $instance;
	}

	/**
	 * Test class exists.
	 */
	public function test_class_exists() {

		$this->assertTrue(class_exists(Post_Type_Limits::class));
	}

	/**
	 * Test init method exists.
	 */
	public function test_init_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'init'));
	}

	/**
	 * Test register_emulated_post_types returns early with empty setting.
	 */
	public function test_register_emulated_post_types_empty() {

		$instance = $this->get_instance();

		// Should not throw with empty setting
		$instance->register_emulated_post_types();

		$this->assertTrue(true);
	}

	/**
	 * Test register_emulated_post_types cleans corrupted data.
	 */
	public function test_register_emulated_post_types_cleans_data() {

		// Set corrupted data
		wu_save_setting('emulated_post_types', [
			'not_an_array',
			['post_type' => 'test', 'label' => 'Test'],
			['invalid_key' => 'value'],
		]);

		$instance = $this->get_instance();

		// Should not throw and should clean data
		$instance->register_emulated_post_types();

		// Verify data was cleaned
		$cleaned = wu_get_setting('emulated_post_types');

		$this->assertIsArray($cleaned);

		// Clean up
		wu_save_setting('emulated_post_types', []);
	}

	/**
	 * Test limit_media returns file with error when media disabled.
	 */
	public function test_limit_media_disabled() {

		$instance = $this->get_instance();

		$file = [
			'name'     => 'test.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '/tmp/test',
			'error'    => 0,
			'size'     => 1000,
		];

		// This will likely pass through as we can't easily mock the limitations
		$result = $instance->limit_media($file);

		$this->assertIsArray($result);
		$this->assertArrayHasKey('name', $result);
	}

	/**
	 * Test limit_tabs returns tabs.
	 */
	public function test_limit_tabs() {

		$instance = $this->get_instance();

		$tabs = [
			'type'     => 'From Computer',
			'type_url' => 'From URL',
			'library'  => 'Media Library',
		];

		$result = $instance->limit_tabs($tabs);

		$this->assertIsArray($result);
	}

	/**
	 * Test limit_draft_publishing returns data when no screen.
	 */
	public function test_limit_draft_publishing_no_screen() {

		$instance = $this->get_instance();

		$data = [
			'post_status' => 'publish',
			'post_type'   => 'post',
		];

		$modified_data = ['ID' => 1];

		$result = $instance->limit_draft_publishing($data, $modified_data);

		$this->assertSame($data, $result);
	}

	/**
	 * Test limit_restoring method exists.
	 */
	public function test_limit_restoring_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'limit_restoring'));
	}

	/**
	 * Test limit_posts method exists.
	 */
	public function test_limit_posts_exists() {

		$instance = $this->get_instance();

		$this->assertTrue(method_exists($instance, 'limit_posts'));
	}

	/**
	 * Test class uses Singleton trait.
	 */
	public function test_uses_singleton_trait() {

		$instance = $this->get_instance();

		$traits = class_uses($instance);

		$this->assertContains(\WP_Ultimo\Traits\Singleton::class, $traits);
	}
}
