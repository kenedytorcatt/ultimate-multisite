<?php
/**
 * Extended tests for site functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for site functions.
 */
class Site_Functions_Extended_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_site returns false for nonexistent.
	 */
	public function test_get_site_nonexistent(): void {

		$result = wu_get_site(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_site_by_hash returns false for nonexistent.
	 */
	public function test_get_site_by_hash_nonexistent(): void {

		$result = wu_get_site_by_hash('nonexistent_hash');

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_sites returns array.
	 */
	public function test_get_sites_returns_array(): void {

		$result = wu_get_sites();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_sites with search.
	 */
	public function test_get_sites_with_search(): void {

		$result = wu_get_sites(['search' => 'nonexistent_xyz']);

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_sites with count.
	 */
	public function test_get_sites_with_count(): void {

		$result = wu_get_sites(['count' => true]);

		$this->assertIsInt($result);
	}

	/**
	 * Test wu_get_site_templates returns array.
	 */
	public function test_get_site_templates(): void {

		$result = wu_get_site_templates();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_current_site returns a Site model.
	 */
	public function test_get_current_site(): void {

		$result = wu_get_current_site();

		$this->assertInstanceOf(\WP_Ultimo\Models\Site::class, $result);
	}

	/**
	 * Test wu_handle_site_domain parses domain correctly.
	 */
	public function test_handle_site_domain(): void {

		$result = wu_handle_site_domain('example.com');

		$this->assertIsObject($result);
		$this->assertObjectHasProperty('host', $result);
		$this->assertEquals('example.com', $result->host);
	}

	/**
	 * Test wu_handle_site_domain with http prefix.
	 */
	public function test_handle_site_domain_with_http(): void {

		$result = wu_handle_site_domain('https://example.com/path');

		$this->assertIsObject($result);
		$this->assertEquals('example.com', $result->host);
		$this->assertEquals('/path', $result->path);
	}

	/**
	 * Test wu_get_site_domain_and_path returns object.
	 */
	public function test_get_site_domain_and_path(): void {

		$result = wu_get_site_domain_and_path('testsite');

		$this->assertIsObject($result);
		$this->assertObjectHasProperty('domain', $result);
		$this->assertObjectHasProperty('path', $result);
	}

	/**
	 * Test wu_generate_site_url_from_title basic conversion.
	 */
	public function test_generate_site_url_from_title(): void {

		$result = wu_generate_site_url_from_title('My Cool Site');

		$this->assertIsString($result);
		$this->assertEquals('mycoolsite', $result);
	}

	/**
	 * Test wu_generate_site_url_from_title with empty string.
	 */
	public function test_generate_site_url_from_title_empty(): void {

		$result = wu_generate_site_url_from_title('');

		$this->assertEquals('', $result);
	}

	/**
	 * Test wu_generate_site_url_from_title with numeric start.
	 */
	public function test_generate_site_url_from_title_numeric_start(): void {

		$result = wu_generate_site_url_from_title('123test');

		$this->assertStringStartsWith('site', $result);
	}

	/**
	 * Test wu_generate_site_url_from_title with special characters.
	 */
	public function test_generate_site_url_from_title_special_chars(): void {

		$result = wu_generate_site_url_from_title('Hello World!');

		$this->assertEquals('helloworld', $result);
	}

	/**
	 * Test wu_generate_site_title_from_email with valid email.
	 */
	public function test_generate_site_title_from_email(): void {

		$result = wu_generate_site_title_from_email('john.doe@example.com');

		$this->assertIsString($result);
		$this->assertNotEmpty($result);
		$this->assertStringContainsString('John', $result);
	}

	/**
	 * Test wu_generate_site_title_from_email with generic prefix.
	 */
	public function test_generate_site_title_from_email_generic(): void {

		$result = wu_generate_site_title_from_email('info@example.com');

		$this->assertIsString($result);
		$this->assertNotEmpty($result);
		// Should use domain part since 'info' is generic
		$this->assertStringContainsString('Example', $result);
	}

	/**
	 * Test wu_generate_site_title_from_email with invalid email.
	 */
	public function test_generate_site_title_from_email_invalid(): void {

		$result = wu_generate_site_title_from_email('not-an-email');

		$this->assertEquals('', $result);
	}

	/**
	 * Test wu_generate_unique_site_url returns string.
	 */
	public function test_generate_unique_site_url(): void {

		$result = wu_generate_unique_site_url('uniquetestsite' . wp_rand());

		$this->assertIsString($result);
		$this->assertNotEmpty($result);
	}
}
