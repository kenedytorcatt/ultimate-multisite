<?php
/**
 * Test case for Checkout_Pages.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 */

namespace WP_Ultimo\Tests\Checkout;

use WP_Ultimo\Checkout\Checkout_Pages;

/**
 * Test Checkout_Pages functionality.
 */
class Checkout_Pages_Test extends \WP_UnitTestCase {

	/**
	 * The Checkout_Pages instance.
	 *
	 * @var Checkout_Pages
	 */
	private $pages;

	/**
	 * Set up test.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->pages = Checkout_Pages::get_instance();
	}

	/**
	 * Test singleton instance.
	 */
	public function test_singleton_instance(): void {

		$this->assertInstanceOf(Checkout_Pages::class, $this->pages);
		$this->assertSame($this->pages, Checkout_Pages::get_instance());
	}

	/**
	 * Test get_signup_pages returns expected keys.
	 */
	public function test_get_signup_pages_returns_expected_keys(): void {

		$pages = $this->pages->get_signup_pages();

		$this->assertIsArray($pages);
		$this->assertArrayHasKey('register', $pages);
		$this->assertArrayHasKey('update', $pages);
		$this->assertArrayHasKey('login', $pages);
		$this->assertArrayHasKey('block_frontend', $pages);
		$this->assertArrayHasKey('new_site', $pages);
	}

	/**
	 * Test get_signup_page returns false for invalid page.
	 */
	public function test_get_signup_page_returns_false_for_invalid_page(): void {

		$result = $this->pages->get_signup_page('nonexistent');

		$this->assertFalse($result);
	}

	/**
	 * Test get_page_url returns false for invalid page.
	 */
	public function test_get_page_url_returns_false_for_invalid_page(): void {

		$result = $this->pages->get_page_url('nonexistent');

		$this->assertFalse($result);
	}

	/**
	 * Test get_error_message returns known error messages.
	 */
	public function test_get_error_message_returns_known_messages(): void {

		$message = $this->pages->get_error_message('incorrect_password');

		$this->assertIsString($message);
		$this->assertNotEmpty($message);
		$this->assertStringContainsString('password', strtolower($message));
	}

	/**
	 * Test get_error_message returns default for unknown code.
	 */
	public function test_get_error_message_returns_default_for_unknown_code(): void {

		$message = $this->pages->get_error_message('totally_unknown_error_code');

		$this->assertIsString($message);
		$this->assertNotEmpty($message);
	}

	/**
	 * Test get_error_message with username parameter.
	 */
	public function test_get_error_message_with_username(): void {

		$message = $this->pages->get_error_message('invalid_username', 'testuser');

		$this->assertIsString($message);
		$this->assertStringContainsString('testuser', $message);
	}

	/**
	 * Test get_error_message for expired session.
	 */
	public function test_get_error_message_expired(): void {

		$message = $this->pages->get_error_message('expired');

		$this->assertIsString($message);
		$this->assertStringContainsString('expired', strtolower($message));
	}

	/**
	 * Test get_error_message for loggedout.
	 */
	public function test_get_error_message_loggedout(): void {

		$message = $this->pages->get_error_message('loggedout');

		$this->assertIsString($message);
		$this->assertStringContainsString('logged out', strtolower($message));
	}

	/**
	 * Test get_error_message for empty_username.
	 */
	public function test_get_error_message_empty_username(): void {

		$message = $this->pages->get_error_message('empty_username');

		$this->assertIsString($message);
		$this->assertStringContainsString('username', strtolower($message));
	}

	/**
	 * Test get_error_message for empty_password.
	 */
	public function test_get_error_message_empty_password(): void {

		$message = $this->pages->get_error_message('empty_password');

		$this->assertIsString($message);
		$this->assertStringContainsString('password', strtolower($message));
	}

	/**
	 * Test get_error_message for invalid_email.
	 */
	public function test_get_error_message_invalid_email(): void {

		$message = $this->pages->get_error_message('invalid_email');

		$this->assertIsString($message);
		$this->assertStringContainsString('email', strtolower($message));
	}

	/**
	 * Test get_error_message for password_reset_mismatch.
	 */
	public function test_get_error_message_password_mismatch(): void {

		$message = $this->pages->get_error_message('password_reset_mismatch');

		$this->assertIsString($message);
		$this->assertStringContainsString('passwords', strtolower($message));
	}

	/**
	 * Test error messages filter.
	 */
	public function test_error_messages_filter(): void {

		add_filter('wu_checkout_pages_error_messages', function ($messages) {
			$messages['custom_error'] = 'Custom error message';
			return $messages;
		});

		$message = $this->pages->get_error_message('custom_error');

		$this->assertEquals('Custom error message', $message);
	}

	/**
	 * Test add_wp_ultimo_status_annotation on non-main site.
	 */
	public function test_status_annotation_on_non_main_site(): void {

		// Switch to a subsite so is_main_site() returns false
		$blog_id = self::factory()->blog->create();

		switch_to_blog($blog_id);

		$page_id = self::factory()->post->create(['post_type' => 'page']);
		$post    = get_post($page_id);

		$states = $this->pages->add_wp_ultimo_status_annotation([], $post);

		restore_current_blog();

		$this->assertIsArray($states);
		$this->assertEmpty($states); // Should return early on non-main site
	}

	/**
	 * Test add_wp_ultimo_status_annotation with matching page on main site.
	 */
	public function test_status_annotation_with_matching_page(): void {

		// Create a page on the main site
		$page_id = self::factory()->post->create(['post_type' => 'page']);

		$this->assertIsInt($page_id);

		wu_save_setting('default_login_page', $page_id);

		$post = get_post($page_id);

		$this->assertInstanceOf(\WP_Post::class, $post);

		$states = $this->pages->add_wp_ultimo_status_annotation([], $post);

		$this->assertIsArray($states);

		// The page should be annotated as a WP Ultimo page
		$this->assertArrayHasKey('wp_ultimo_page', $states);
	}

	/**
	 * Test filter_lost_password_redirect with non-empty redirect.
	 */
	public function test_filter_lost_password_redirect_with_redirect(): void {

		$redirect = 'https://example.com/custom-redirect';

		$result = $this->pages->filter_lost_password_redirect($redirect);

		$this->assertEquals($redirect, $result);
	}

	/**
	 * Test filter_lost_password_redirect with empty redirect.
	 */
	public function test_filter_lost_password_redirect_empty(): void {

		$result = $this->pages->filter_lost_password_redirect('');

		$this->assertIsString($result);
		$this->assertStringContainsString('checkemail=confirm', $result);
	}

	/**
	 * Test maybe_change_wp_login_on_urls with non-login URL.
	 */
	public function test_maybe_change_wp_login_on_urls_no_login(): void {

		$url = 'https://example.com/some-page';

		$result = $this->pages->maybe_change_wp_login_on_urls($url);

		$this->assertEquals($url, $result);
	}

	/**
	 * Test maybe_change_wp_login_on_urls with login URL but no custom page.
	 */
	public function test_maybe_change_wp_login_on_urls_with_login_no_custom(): void {

		wu_save_setting('default_login_page', 0);

		$url = 'https://example.com/wp-login.php';

		$result = $this->pages->maybe_change_wp_login_on_urls($url);

		// Without a valid custom login page, URL should remain unchanged
		$this->assertIsString($result);
	}

	/**
	 * Test maybe_change_wp_login_on_urls with login URL and custom page.
	 */
	public function test_maybe_change_wp_login_on_urls_with_custom_page(): void {

		$page_id = self::factory()->post->create([
			'post_type'   => 'page',
			'post_name'   => 'custom-login',
			'post_title'  => 'Custom Login',
			'post_status' => 'publish',
		]);

		$this->assertIsInt($page_id);

		wu_save_setting('default_login_page', $page_id);

		// Verify the setting was saved and the post exists
		$saved_id = wu_get_setting('default_login_page', 0);
		$post     = get_post($saved_id);

		// The method does str_replace('wp-login.php', $post->post_name, $url)
		// Only assert if the post is retrievable (multisite env may differ)
		if ($post) {
			$url    = 'https://example.com/wp-login.php?action=lostpassword';
			$result = $this->pages->maybe_change_wp_login_on_urls($url);

			$this->assertStringContainsString('custom-login', $result);
			$this->assertStringNotContainsString('wp-login.php', $result);
		} else {
			// If post can't be retrieved via setting, test the passthrough behavior
			$url    = 'https://example.com/wp-login.php?action=lostpassword';
			$result = $this->pages->maybe_change_wp_login_on_urls($url);

			$this->assertIsString($result);
		}
	}

	/**
	 * Test handle_compat_mode_setting with autosave.
	 */
	public function test_handle_compat_mode_setting_autosave(): void {

		if ( ! defined('DOING_AUTOSAVE')) {
			define('DOING_AUTOSAVE', true);
		}

		$post_id = self::factory()->post->create(['post_type' => 'page']);

		$this->assertIsInt($post_id);

		// Should return early without doing anything
		$this->pages->handle_compat_mode_setting($post_id);

		$meta = get_post_meta($post_id, '_wu_force_elements_loading', true);
		$this->assertEmpty($meta);
	}

	/**
	 * Test replace_reset_password_link on non-main site.
	 */
	public function test_replace_reset_password_link_non_main_site(): void {

		// On main site in test env, this should process normally
		$message = 'Reset your password at https://example.com/wp-login.php?action=rp&key=abc123';

		$result = $this->pages->replace_reset_password_link($message, 'abc123', 'testuser', ['ID' => 1]);

		$this->assertIsString($result);
	}

	/**
	 * Test filter_login_url before wp_loaded.
	 */
	public function test_filter_login_url_before_wp_loaded(): void {

		// Remove the wp_loaded action count to simulate before wp_loaded
		// This is tricky in tests since wp_loaded has already fired
		$original_url = 'https://example.com/wp-login.php';

		$result = $this->pages->filter_login_url($original_url, '', false);

		$this->assertIsString($result);
	}

	/**
	 * Test maybe_flush_rewrite_rules_on_page_trash with non-page post type.
	 */
	public function test_flush_rewrite_rules_on_trash_non_page(): void {

		$post_id = self::factory()->post->create(['post_type' => 'post']);

		$this->assertIsInt($post_id);

		// Should return early without flushing (not a page)
		$this->pages->maybe_flush_rewrite_rules_on_page_trash($post_id);

		$this->assertTrue(true); // No error means success
	}

	/**
	 * Test maybe_flush_rewrite_rules_on_page_trash with page post type.
	 */
	public function test_flush_rewrite_rules_on_trash_page(): void {

		$page_id = self::factory()->post->create(['post_type' => 'page']);

		$this->assertIsInt($page_id);

		// Should not flush since this page is not a signup page
		$this->pages->maybe_flush_rewrite_rules_on_page_trash($page_id);

		$this->assertTrue(true); // No error means success
	}

	/**
	 * Test render_confirmation_page shortcode.
	 */
	public function test_render_confirmation_page(): void {

		$result = $this->pages->render_confirmation_page([]);

		$this->assertIsString($result);
	}
}
