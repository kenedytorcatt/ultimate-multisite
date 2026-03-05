<?php
/**
 * Unit tests for MCP_Abilities trait.
 *
 * @package WP_Ultimo\Tests\Apis
 */

namespace WP_Ultimo\Tests\Apis;

use WP_Ultimo\Apis\MCP_Abilities;

/**
 * Mock manager class for testing MCP_Abilities trait.
 */
class Mock_MCP_Manager {

	use MCP_Abilities;

	protected $slug = 'test_entity';

	protected $model_class = '\WP_Ultimo\Models\Customer';

	public function get_arguments_schema($for_update = false) {
		return [
			'email_address' => [
				'type'        => 'string',
				'description' => 'Email address',
				'required'    => true,
			],
			'user_id'       => [
				'type'        => 'integer',
				'description' => 'User ID',
			],
			'vip'           => [
				'type'        => 'boolean',
				'description' => 'VIP status',
				'default'     => false,
			],
		];
	}
}

/**
 * Test the MCP_Abilities trait.
 */
class MCP_Abilities_Test extends \WP_UnitTestCase {

	/**
	 * Mock manager instance.
	 *
	 * @var Mock_MCP_Manager
	 */
	protected $manager;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {

		parent::setUp();

		$this->manager = new Mock_MCP_Manager();
	}

	/**
	 * Test get_mcp_ability_prefix returns correct prefix.
	 */
	public function test_get_mcp_ability_prefix(): void {

		$prefix = $this->manager->get_mcp_ability_prefix();

		$this->assertEquals('multisite-ultimate/test-entity', $prefix);
	}

	/**
	 * Test get_mcp_schema_for_ability generates schema for create.
	 */
	public function test_get_mcp_schema_for_ability_create(): void {

		$schema = $this->manager->get_mcp_schema_for_ability('create');

		$this->assertIsArray($schema);
		$this->assertArrayHasKey('type', $schema);
		$this->assertEquals('object', $schema['type']);
		$this->assertArrayHasKey('properties', $schema);
		$this->assertArrayHasKey('email_address', $schema['properties']);
		$this->assertArrayHasKey('user_id', $schema['properties']);
		$this->assertArrayHasKey('vip', $schema['properties']);
	}

	/**
	 * Test get_mcp_schema_for_ability marks required fields.
	 */
	public function test_get_mcp_schema_marks_required_fields(): void {

		$schema = $this->manager->get_mcp_schema_for_ability('create');

		$this->assertArrayHasKey('required', $schema);
		$this->assertContains('email_address', $schema['required']);
	}

	/**
	 * Test get_mcp_schema_for_ability adds defaults to descriptions.
	 */
	public function test_get_mcp_schema_includes_defaults_in_description(): void {

		$schema = $this->manager->get_mcp_schema_for_ability('create');

		$this->assertStringContainsString('Defaults to false', $schema['properties']['vip']['description']);
	}

	/**
	 * Test sanitize_json_schema_type handles basic types.
	 */
	public function test_sanitize_json_schema_type_basic_types(): void {

		$reflection = new \ReflectionClass($this->manager);
		$method     = $reflection->getMethod('sanitize_json_schema_type');

		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		$this->assertEquals('boolean', $method->invoke(null, 'bool'));
		$this->assertEquals('integer', $method->invoke(null, 'int'));
		$this->assertEquals('number', $method->invoke(null, 'float'));
		$this->assertEquals('string', $method->invoke(null, 'string'));
	}

	/**
	 * Test sanitize_json_schema_type handles union types.
	 */
	public function test_sanitize_json_schema_type_union_types(): void {

		$reflection = new \ReflectionClass($this->manager);
		$method     = $reflection->getMethod('sanitize_json_schema_type');

		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		$result = $method->invoke(null, 'int|null');

		$this->assertIsArray($result);
		$this->assertContains('integer', $result);
		$this->assertContains('null', $result);
	}

	/**
	 * Test sanitize_json_schema_type handles class names.
	 */
	public function test_sanitize_json_schema_type_class_names(): void {

		$reflection = new \ReflectionClass($this->manager);
		$method     = $reflection->getMethod('sanitize_json_schema_type');

		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		// Class names should default to string
		$result = $method->invoke(null, '\WP_Ultimo\Models\Customer');

		$this->assertEquals('string', $result);
	}

	/**
	 * Test get_model_filter_columns returns filters for known models.
	 */
	public function test_get_model_filter_columns_returns_filters(): void {

		// Create a manager with a known slug
		$manager       = new Mock_MCP_Manager();
		$manager->slug = 'customer';

		$filters = $manager->get_model_filter_columns();

		$this->assertIsArray($filters);
		$this->assertArrayHasKey('user_id', $filters);
		$this->assertArrayHasKey('type', $filters);
		$this->assertArrayHasKey('vip', $filters);
	}

	/**
	 * Test get_model_filter_columns returns empty for unknown models.
	 */
	public function test_get_model_filter_columns_empty_for_unknown(): void {

		$manager       = new Mock_MCP_Manager();
		$manager->slug = 'unknown_entity';

		$filters = $manager->get_model_filter_columns();

		$this->assertIsArray($filters);
		$this->assertEmpty($filters);
	}

	/**
	 * Test get_mcp_filter_properties generates filter properties.
	 */
	public function test_get_mcp_filter_properties(): void {

		$manager       = new Mock_MCP_Manager();
		$manager->slug = 'customer';

		$properties = $manager->get_mcp_filter_properties();

		$this->assertIsArray($properties);
		$this->assertArrayHasKey('user_id', $properties);
		$this->assertArrayHasKey('description', $properties['user_id']);
		$this->assertArrayHasKey('type', $properties['user_id']);
	}

	/**
	 * Test mcp_permission_callback checks capabilities.
	 */
	public function test_mcp_permission_callback_checks_capability(): void {

		// Create user without capability
		$user_id = $this->factory()->user->create();
		wp_set_current_user($user_id);

		$result = $this->manager->mcp_permission_callback([]);

		$this->assertInstanceOf('\WP_Error', $result);
		$this->assertEquals('rest_forbidden', $result->get_error_code());
	}

	/**
	 * Test mcp_permission_callback allows network admin.
	 */
	public function test_mcp_permission_callback_allows_network_admin(): void {

		// Create super admin
		$user_id = $this->factory()->user->create();
		grant_super_admin($user_id);
		wp_set_current_user($user_id);

		$result = $this->manager->mcp_permission_callback([]);

		$this->assertTrue($result);
	}

	/**
	 * Test format_validation_error combines multiple errors.
	 */
	public function test_format_validation_error_combines_errors(): void {

		$wp_error = new \WP_Error();
		$wp_error->add('error1', 'First error');
		$wp_error->add('error2', 'Second error');

		$reflection = new \ReflectionClass($this->manager);
		$method     = $reflection->getMethod('format_validation_error');

		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		$result = $method->invoke($this->manager, $wp_error);

		$this->assertInstanceOf('\WP_Error', $result);
		$message = $result->get_error_message();
		$this->assertStringContainsString('First error', $message);
		$this->assertStringContainsString('Second error', $message);
	}

	/**
	 * Test format_validation_error returns single error unchanged.
	 */
	public function test_format_validation_error_single_error_unchanged(): void {

		$wp_error = new \WP_Error('single_error', 'Single message');

		$reflection = new \ReflectionClass($this->manager);
		$method     = $reflection->getMethod('format_validation_error');

		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}

		$result = $method->invoke($this->manager, $wp_error);

		$this->assertSame($wp_error, $result);
	}

	/**
	 * Test schema caching works correctly.
	 */
	public function test_schema_caching(): void {

		// Call twice to test cache
		$schema1 = $this->manager->get_mcp_schema_for_ability('create');
		$schema2 = $this->manager->get_mcp_schema_for_ability('create');

		// Should return same cached instance
		$this->assertSame($schema1, $schema2);
	}

	/**
	 * Test schema caching differentiates contexts.
	 */
	public function test_schema_caching_different_contexts(): void {

		$create_schema = $this->manager->get_mcp_schema_for_ability('create');
		$update_schema = $this->manager->get_mcp_schema_for_ability('update');

		// Should be different arrays
		$this->assertNotSame($create_schema, $update_schema);
	}

	/**
	 * Test boundary: Empty slug.
	 */
	public function test_empty_slug(): void {

		$manager       = new Mock_MCP_Manager();
		$manager->slug = '';

		$prefix = $manager->get_mcp_ability_prefix();

		$this->assertEquals('multisite-ultimate/', $prefix);
	}
}