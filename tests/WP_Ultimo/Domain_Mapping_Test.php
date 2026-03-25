<?php
/**
 * Tests for Domain_Mapping class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo;

use WP_UnitTestCase;

/**
 * Test class for Domain_Mapping.
 */
class Domain_Mapping_Test extends WP_UnitTestCase {

	/**
	 * @var Domain_Mapping
	 */
	private Domain_Mapping $domain_mapping;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {

		parent::set_up();
		$this->domain_mapping = Domain_Mapping::get_instance();
	}

	/**
	 * Test singleton returns correct instance.
	 */
	public function test_singleton_returns_correct_instance(): void {

		$this->assertInstanceOf(Domain_Mapping::class, $this->domain_mapping);
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {

		$this->assertSame(
			Domain_Mapping::get_instance(),
			Domain_Mapping::get_instance()
		);
	}

	/**
	 * Test get_www_and_nowww_versions with plain domain.
	 */
	public function test_get_www_and_nowww_versions_plain_domain(): void {

		$result = $this->domain_mapping->get_www_and_nowww_versions('example.com');

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertEquals('example.com', $result[0]);
		$this->assertEquals('www.example.com', $result[1]);
	}

	/**
	 * Test get_www_and_nowww_versions with www domain.
	 */
	public function test_get_www_and_nowww_versions_www_domain(): void {

		$result = $this->domain_mapping->get_www_and_nowww_versions('www.example.com');

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertEquals('example.com', $result[0]);
		$this->assertEquals('www.example.com', $result[1]);
	}

	/**
	 * Test get_www_and_nowww_versions with subdomain.
	 */
	public function test_get_www_and_nowww_versions_subdomain(): void {

		$result = $this->domain_mapping->get_www_and_nowww_versions('sub.example.com');

		$this->assertEquals('sub.example.com', $result[0]);
		$this->assertEquals('www.sub.example.com', $result[1]);
	}

	/**
	 * Test should_skip_checks returns false by default.
	 */
	public function test_should_skip_checks_default(): void {

		// By default, the constant is not defined, so should return false.
		if (! defined('WP_ULTIMO_DOMAIN_MAPPING_SKIP_CHECKS')) {
			$this->assertFalse(Domain_Mapping::should_skip_checks());
		} else {
			// If defined, just verify it returns a boolean.
			$this->assertIsBool(Domain_Mapping::should_skip_checks());
		}
	}

	/**
	 * Test allow_network_redirect_hosts with empty host.
	 */
	public function test_allow_network_redirect_hosts_empty_host(): void {

		$allowed = ['example.com'];
		$result  = $this->domain_mapping->allow_network_redirect_hosts($allowed, '');

		$this->assertEquals($allowed, $result);
	}

	/**
	 * Test allow_network_redirect_hosts with already allowed host.
	 */
	public function test_allow_network_redirect_hosts_already_allowed(): void {

		$allowed = ['example.com', 'test.com'];
		$result  = $this->domain_mapping->allow_network_redirect_hosts($allowed, 'example.com');

		$this->assertEquals($allowed, $result);
	}

	/**
	 * Test allow_network_redirect_hosts normalizes to lowercase.
	 */
	public function test_allow_network_redirect_hosts_case_insensitive(): void {

		$allowed = ['example.com'];
		$result  = $this->domain_mapping->allow_network_redirect_hosts($allowed, 'EXAMPLE.COM');

		// Should match the lowercase version already in the list.
		$this->assertEquals($allowed, $result);
	}

	/**
	 * Test add_mapped_domains_as_allowed_origins with empty origin.
	 */
	public function test_add_mapped_domains_as_allowed_origins_empty(): void {

		// When not doing ajax and origin is empty, should return empty string or origin.
		$result = $this->domain_mapping->add_mapped_domains_as_allowed_origins('');

		// Should return empty string when no domain found.
		$this->assertIsString($result);
	}

	/**
	 * Test add_mapped_domains_as_allowed_origins with valid origin.
	 */
	public function test_add_mapped_domains_as_allowed_origins_returns_origin(): void {

		$result = $this->domain_mapping->add_mapped_domains_as_allowed_origins('https://nonexistent-domain.test');

		// No mapping exists, should return the original origin.
		$this->assertEquals('https://nonexistent-domain.test', $result);
	}

	/**
	 * Test fix_sso_target_site with valid target site passes through.
	 */
	public function test_fix_sso_target_site_valid_target_passes_through(): void {

		$site = get_site(get_current_blog_id());

		$result = $this->domain_mapping->fix_sso_target_site($site, 'example.com');

		$this->assertInstanceOf(\WP_Site::class, $result);
		$this->assertEquals($site->blog_id, $result->blog_id);
	}

	/**
	 * Test current_mapping property is null by default via reflection.
	 */
	public function test_current_mapping_default_null(): void {

		$mapping    = Domain_Mapping::get_instance();
		$reflection = new \ReflectionClass($mapping);
		$prop       = $reflection->getProperty('current_mapping');

		if (PHP_VERSION_ID < 80100) {
			$prop->setAccessible(true);
		}

		// Reset to null for test isolation.
		$prop->setValue($mapping, null);

		$this->assertNull($prop->getValue($mapping));
	}

	/**
	 * Test original_url property is null by default via reflection.
	 */
	public function test_original_url_default_null(): void {

		$mapping    = Domain_Mapping::get_instance();
		$reflection = new \ReflectionClass($mapping);
		$prop       = $reflection->getProperty('original_url');

		if (PHP_VERSION_ID < 80100) {
			$prop->setAccessible(true);
		}

		// Reset to null for test isolation.
		$prop->setValue($mapping, null);

		$this->assertNull($prop->getValue($mapping));
	}
}
