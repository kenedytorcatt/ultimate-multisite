<?php
/**
 * Tests for email functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for email functions.
 */
class Email_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_email returns false for nonexistent.
	 */
	public function test_get_email_nonexistent(): void {

		$result = wu_get_email(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_email_by returns false for nonexistent.
	 */
	public function test_get_email_by_nonexistent(): void {

		$result = wu_get_email_by('id', 999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_emails returns array.
	 */
	public function test_get_emails_returns_array(): void {

		$result = wu_get_emails();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_get_all_system_emails returns array.
	 */
	public function test_get_all_system_emails_returns_array(): void {

		$result = wu_get_all_system_emails();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_format_email_string with name.
	 */
	public function test_format_email_string_with_name(): void {

		$result = wu_format_email_string('john@example.com', 'John Doe');

		$this->assertEquals('John Doe <john@example.com>', $result);
	}

	/**
	 * Test wu_format_email_string without name.
	 */
	public function test_format_email_string_without_name(): void {

		$result = wu_format_email_string('john@example.com');

		$this->assertEquals('john@example.com', $result);
	}

	/**
	 * Test wu_format_email_string with false name.
	 */
	public function test_format_email_string_false_name(): void {

		$result = wu_format_email_string('test@example.com', false);

		$this->assertEquals('test@example.com', $result);
	}

	/**
	 * Test wu_create_email creates an email.
	 */
	public function test_create_email(): void {

		$email = wu_create_email([
			'type'            => 'system_email',
			'event'           => 'test_event',
			'title'           => 'Test Email',
			'slug'            => 'test-email-' . wp_rand(),
			'target'          => 'admin',
			'status'          => 'publish',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($email);
		$this->assertInstanceOf(\WP_Ultimo\Models\Email::class, $email);
	}

	/**
	 * Test wu_get_email retrieves created email.
	 */
	public function test_get_email_retrieves_created(): void {

		$email = wu_create_email([
			'type'            => 'system_email',
			'event'           => 'test_event_retrieve',
			'title'           => 'Retrieve Test Email',
			'slug'            => 'retrieve-test-email-' . wp_rand(),
			'target'          => 'admin',
			'status'          => 'publish',
			'skip_validation' => true,
		]);

		$this->assertNotWPError($email);

		$retrieved = wu_get_email($email->get_id());

		$this->assertNotFalse($retrieved);
		$this->assertEquals($email->get_id(), $retrieved->get_id());
	}

	/**
	 * Test wu_get_default_system_emails returns array.
	 */
	public function test_get_default_system_emails(): void {

		$result = wu_get_default_system_emails();

		$this->assertIsArray($result);
	}
}
