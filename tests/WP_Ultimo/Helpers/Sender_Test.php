<?php
/**
 * Tests for the Sender helper class.
 */

namespace WP_Ultimo\Helpers;

use WP_UnitTestCase;

/**
 * @group sender
 */
class Sender_Test extends WP_UnitTestCase {

	// ------------------------------------------------------------------
	// parse_args
	// ------------------------------------------------------------------

	public function test_parse_args_returns_array() {
		$result = Sender::parse_args();
		$this->assertIsArray($result);
	}

	public function test_parse_args_has_default_keys() {
		$result = Sender::parse_args();

		$this->assertArrayHasKey('from', $result);
		$this->assertArrayHasKey('content', $result);
		$this->assertArrayHasKey('subject', $result);
		$this->assertArrayHasKey('bcc', $result);
		$this->assertArrayHasKey('payload', $result);
		$this->assertArrayHasKey('attachments', $result);
		$this->assertArrayHasKey('style', $result);
	}

	public function test_parse_args_from_has_name_and_email() {
		$result = Sender::parse_args();

		$this->assertArrayHasKey('name', $result['from']);
		$this->assertArrayHasKey('email', $result['from']);
	}

	public function test_parse_args_merges_custom_values() {
		$result = Sender::parse_args([
			'subject' => 'Custom Subject',
			'content' => 'Custom Content',
		]);

		$this->assertEquals('Custom Subject', $result['subject']);
		$this->assertEquals('Custom Content', $result['content']);
	}

	public function test_parse_args_preserves_defaults_for_missing_keys() {
		$result = Sender::parse_args([
			'subject' => 'Only Subject',
		]);

		$this->assertEquals('Only Subject', $result['subject']);
		$this->assertEquals('', $result['content']);
		$this->assertIsArray($result['bcc']);
	}

	// ------------------------------------------------------------------
	// process_shortcodes
	// ------------------------------------------------------------------

	public function test_process_shortcodes_replaces_placeholders() {
		$content = 'Hello {{name}}, welcome to {{site}}!';
		$payload = [
			'name' => 'John',
			'site' => 'My Site',
		];

		$result = Sender::process_shortcodes($content, $payload);
		$this->assertEquals('Hello John, welcome to My Site!', $result);
	}

	public function test_process_shortcodes_returns_original_with_empty_payload() {
		$content = 'Hello {{name}}!';
		$result = Sender::process_shortcodes($content, []);

		$this->assertEquals($content, $result);
	}

	public function test_process_shortcodes_handles_missing_placeholders() {
		$content = 'Hello {{name}}, your email is {{email}}';
		$payload = [
			'name' => 'Jane',
		];

		$result = Sender::process_shortcodes($content, $payload);
		$this->assertStringContainsString('Jane', $result);
	}

	public function test_process_shortcodes_handles_no_placeholders() {
		$content = 'No placeholders here';
		$payload = ['key' => 'value'];

		$result = Sender::process_shortcodes($content, $payload);
		$this->assertEquals('No placeholders here', $result);
	}

	public function test_process_shortcodes_handles_multiple_same_placeholder() {
		$content = '{{name}} is {{name}}';
		$payload = ['name' => 'Bob'];

		$result = Sender::process_shortcodes($content, $payload);
		$this->assertEquals('Bob is Bob', $result);
	}

	public function test_process_shortcodes_handles_numeric_values() {
		$content = 'Amount: {{amount}}';
		$payload = ['amount' => 42];

		$result = Sender::process_shortcodes($content, $payload);
		$this->assertStringContainsString('42', $result);
	}
}
