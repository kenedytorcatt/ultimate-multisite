<?php
/**
 * Tests for webhook functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for webhook functions.
 */
class Webhook_Functions_Test extends WP_UnitTestCase {

	/**
	 * Test wu_get_webhook returns false for nonexistent.
	 */
	public function test_get_webhook_nonexistent(): void {

		$result = wu_get_webhook(999999);

		$this->assertFalse($result);
	}

	/**
	 * Test wu_get_webhooks returns array.
	 */
	public function test_get_webhooks_returns_array(): void {

		$result = wu_get_webhooks();

		$this->assertIsArray($result);
	}

	/**
	 * Test wu_create_webhook creates a webhook.
	 */
	public function test_create_webhook(): void {

		$webhook = wu_create_webhook([
			'name'            => 'Test Webhook',
			'webhook_url'     => 'https://example.com/webhook',
			'event'           => 'payment_received',
			'active'          => true,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($webhook);
		$this->assertInstanceOf(\WP_Ultimo\Models\Webhook::class, $webhook);
	}

	/**
	 * Test wu_get_webhook retrieves created webhook.
	 */
	public function test_get_webhook_retrieves_created(): void {

		$webhook = wu_create_webhook([
			'name'            => 'Retrieve Test Webhook',
			'webhook_url'     => 'https://example.com/webhook-retrieve',
			'event'           => 'payment_received',
			'active'          => true,
			'skip_validation' => true,
		]);

		$this->assertNotWPError($webhook);

		$retrieved = wu_get_webhook($webhook->get_id());

		$this->assertNotFalse($retrieved);
		$this->assertEquals($webhook->get_id(), $retrieved->get_id());
	}
}
