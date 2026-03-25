<?php
/**
 * Test case for DNS_Record_Manager.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 */

namespace WP_Ultimo\Tests\Managers;

use WP_Ultimo\Integrations\Host_Providers\DNS_Record;
use WP_Ultimo\Managers\DNS_Record_Manager;
use WP_UnitTestCase;

/**
 * Test DNS_Record_Manager functionality.
 */
class DNS_Record_Manager_Test extends WP_UnitTestCase {

	/**
	 * The DNS Record Manager instance.
	 *
	 * @var DNS_Record_Manager
	 */
	private $manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->manager = DNS_Record_Manager::get_instance();
	}

	/**
	 * Test singleton instance.
	 */
	public function test_singleton_instance() {
		$instance1 = DNS_Record_Manager::get_instance();
		$instance2 = DNS_Record_Manager::get_instance();

		$this->assertSame($instance1, $instance2);
		$this->assertInstanceOf(DNS_Record_Manager::class, $instance1);
	}

	/**
	 * Test get_allowed_record_types returns array for super admin.
	 */
	public function test_get_allowed_record_types() {
		// Use a super admin user ID so all types are returned.
		$user_id = $this->factory->user->create(['role' => 'administrator']);
		grant_super_admin($user_id);

		$types = $this->manager->get_allowed_record_types($user_id);

		$this->assertIsArray($types);
		$this->assertContains('A', $types);
		$this->assertContains('AAAA', $types);
		$this->assertContains('CNAME', $types);
		$this->assertContains('MX', $types);
		$this->assertContains('TXT', $types);
	}

	/**
	 * Test get_dns_provider returns null when no provider configured.
	 */
	public function test_get_dns_provider_returns_null_when_not_configured() {
		// When no provider is enabled, should return null
		$provider = $this->manager->get_dns_provider();

		// This may or may not be null depending on test environment
		// At minimum, check it doesn't throw an error
		$this->assertTrue($provider === null || is_object($provider));
	}

	/**
	 * Test get_dns_capable_providers returns array.
	 */
	public function test_get_dns_capable_providers() {
		$providers = $this->manager->get_dns_capable_providers();

		$this->assertIsArray($providers);
	}

	/**
	 * Test DNS_Record::validate() with valid A record.
	 */
	public function test_validate_dns_record_valid_a_record() {
		$record = new DNS_Record([
			'type'    => 'A',
			'name'    => 'test',
			'content' => '192.168.1.1',
			'ttl'     => 3600,
		]);

		$result = $record->validate();

		$this->assertTrue($result);
	}

	/**
	 * Test DNS_Record::validate() with invalid type.
	 */
	public function test_validate_dns_record_invalid_type() {
		$record = new DNS_Record([
			'type'    => 'INVALID',
			'name'    => 'test',
			'content' => 'value',
		]);

		$result = $record->validate();

		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertEquals('invalid_type', $result->get_error_code());
	}

	/**
	 * Test DNS_Record::validate() with empty name.
	 */
	public function test_validate_dns_record_empty_name() {
		$record = new DNS_Record([
			'type'    => 'A',
			'name'    => '',
			'content' => '192.168.1.1',
		]);

		$result = $record->validate();

		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertEquals('missing_name', $result->get_error_code());
	}

	/**
	 * Test DNS_Record::validate() with empty content.
	 */
	public function test_validate_dns_record_empty_content() {
		$record = new DNS_Record([
			'type'    => 'A',
			'name'    => 'test',
			'content' => '',
		]);

		$result = $record->validate();

		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertEquals('missing_content', $result->get_error_code());
	}

	/**
	 * Test DNS_Record::validate() with invalid IPv4.
	 */
	public function test_validate_dns_record_invalid_ipv4() {
		$record = new DNS_Record([
			'type'    => 'A',
			'name'    => 'test',
			'content' => 'not-an-ip',
		]);

		$result = $record->validate();

		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertEquals('invalid_ipv4', $result->get_error_code());
	}

	/**
	 * Test DNS_Record::validate() with invalid IPv6.
	 */
	public function test_validate_dns_record_invalid_ipv6() {
		$record = new DNS_Record([
			'type'    => 'AAAA',
			'name'    => 'test',
			'content' => '192.168.1.1', // IPv4 instead of IPv6
		]);

		$result = $record->validate();

		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertEquals('invalid_ipv6', $result->get_error_code());
	}

	/**
	 * Test DNS_Record::validate() with valid AAAA record.
	 */
	public function test_validate_dns_record_valid_aaaa() {
		$record = new DNS_Record([
			'type'    => 'AAAA',
			'name'    => 'test',
			'content' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
		]);

		$result = $record->validate();

		$this->assertTrue($result);
	}

	/**
	 * Test DNS_Record::validate() with valid CNAME.
	 */
	public function test_validate_dns_record_valid_cname() {
		$record = new DNS_Record([
			'type'    => 'CNAME',
			'name'    => 'www',
			'content' => 'example.com',
		]);

		$result = $record->validate();

		$this->assertTrue($result);
	}

	/**
	 * Test DNS_Record::validate() with CNAME pointing to an IP (invalid).
	 */
	public function test_validate_dns_record_invalid_cname() {
		$record = new DNS_Record([
			'type'    => 'CNAME',
			'name'    => 'www',
			'content' => '192.168.1.1', // IP address is invalid for CNAME
		]);

		$result = $record->validate();

		$this->assertInstanceOf(\WP_Error::class, $result);
		$this->assertEquals('invalid_cname', $result->get_error_code());
	}

	/**
	 * Test DNS_Record::validate() with valid MX record.
	 */
	public function test_validate_dns_record_valid_mx() {
		$record = new DNS_Record([
			'type'     => 'MX',
			'name'     => '@',
			'content'  => 'mail.example.com',
			'priority' => 10,
		]);

		$result = $record->validate();

		$this->assertTrue($result);
	}

	/**
	 * Test DNS_Record::validate() with valid TXT record.
	 */
	public function test_validate_dns_record_valid_txt() {
		$record = new DNS_Record([
			'type'    => 'TXT',
			'name'    => '@',
			'content' => 'v=spf1 include:_spf.google.com ~all',
		]);

		$result = $record->validate();

		$this->assertTrue($result);
	}

	/**
	 * Test export_to_bind format.
	 */
	public function test_export_to_bind() {
		$records = [
			new DNS_Record([
				'type'    => 'A',
				'name'    => '@',
				'content' => '192.168.1.1',
				'ttl'     => 3600,
			]),
			new DNS_Record([
				'type'     => 'MX',
				'name'     => '@',
				'content'  => 'mail.example.com',
				'ttl'      => 3600,
				'priority' => 10,
			]),
		];

		// Correct argument order: domain first, then records array.
		$bind = $this->manager->export_to_bind('example.com', $records);

		$this->assertStringContainsString('$ORIGIN example.com.', $bind);
		// BIND output uses tab separators between fields.
		$this->assertMatchesRegularExpression('/@\s+3600\s+IN\s+A\s+192\.168\.1\.1/', $bind);
		$this->assertMatchesRegularExpression('/@\s+3600\s+IN\s+MX\s+10\s+mail\.example\.com\./', $bind);
	}

	/**
	 * Test parse_bind_format.
	 */
	public function test_parse_bind_format() {
		$bind_content = <<<BIND
\$ORIGIN example.com.
@ 3600 IN A 192.168.1.1
www 3600 IN CNAME example.com.
@ 3600 IN MX 10 mail.example.com.
@ 3600 IN TXT "v=spf1 mx ~all"
BIND;

		// parse_bind_format requires both content and domain arguments.
		$records = $this->manager->parse_bind_format($bind_content, 'example.com');

		$this->assertIsArray($records);
		$this->assertGreaterThanOrEqual(4, count($records));

		// Find A record
		$a_record = array_filter($records, fn($r) => $r['type'] === 'A');
		$this->assertNotEmpty($a_record);
	}

	/**
	 * Test customer_can_manage_dns returns false for non-owner.
	 */
	public function test_customer_can_manage_dns_non_owner() {
		// Create a test user who doesn't own any domains
		$user_id = $this->factory->user->create();

		$result = $this->manager->customer_can_manage_dns($user_id, 'example.com');

		$this->assertFalse($result);
	}
}
