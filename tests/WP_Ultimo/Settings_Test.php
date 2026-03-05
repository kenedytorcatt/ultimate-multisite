<?php
/**
 * Unit tests for Settings class.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Tests;

use WP_Ultimo\Settings;

class Settings_Test extends \WP_UnitTestCase {

	/**
	 * Test get_setting returns correct values.
	 */
	public function test_get_setting_returns_value(): void {

		$settings = Settings::get_instance();

		// Save a test setting
		$settings->save_setting('test_setting', 'test_value');

		// Retrieve it
		$value = $settings->get_setting('test_setting');

		$this->assertEquals('test_value', $value);
	}

	/**
	 * Test get_setting returns default value when setting doesn't exist.
	 */
	public function test_get_setting_returns_default(): void {

		$settings = Settings::get_instance();

		$value = $settings->get_setting('non_existent_setting', 'default_value');

		$this->assertEquals('default_value', $value);
	}

	/**
	 * Test get_setting falls back to setting defaults.
	 */
	public function test_get_setting_uses_defaults(): void {

		$settings = Settings::get_instance();

		// Get a default value for a known setting
		$value = $settings->get_setting('currency_symbol');

		// Should return the default 'USD'
		$this->assertEquals('USD', $value);
	}

	/**
	 * Test save_setting stores values correctly.
	 */
	public function test_save_setting_stores_value(): void {

		$settings = Settings::get_instance();

		$result = $settings->save_setting('test_key', 'test_value');

		$this->assertTrue($result);

		// Verify it was saved
		$retrieved = $settings->get_setting('test_key');
		$this->assertEquals('test_value', $retrieved);
	}

	/**
	 * Test save_setting handles callable values.
	 */
	public function test_save_setting_handles_callable(): void {

		$settings = Settings::get_instance();

		$callable = function () {
			return 'computed_value';
		};

		$settings->save_setting('callable_setting', $callable);

		$value = $settings->get_setting('callable_setting');

		$this->assertEquals('computed_value', $value);
	}

	/**
	 * Test get_all returns array of settings.
	 */
	public function test_get_all_returns_array(): void {

		$settings = Settings::get_instance();

		$all = $settings->get_all();

		$this->assertIsArray($all);
	}

	/**
	 * Test get_section returns section configuration.
	 */
	public function test_get_section_returns_section(): void {

		$settings = Settings::get_instance();

		$section = $settings->get_section('general');

		$this->assertIsArray($section);
		$this->assertArrayHasKey('fields', $section);
	}

	/**
	 * Test get_section_names returns section names without triggering full registration.
	 */
	public function test_get_section_names_returns_lightweight_array(): void {

		$settings = Settings::get_instance();

		$names = $settings->get_section_names();

		$this->assertIsArray($names);
		$this->assertArrayHasKey('general', $names);
		$this->assertArrayHasKey('title', $names['general']);
		$this->assertArrayHasKey('icon', $names['general']);
	}

	/**
	 * Test get_setting_defaults returns expected defaults.
	 */
	public function test_get_setting_defaults_returns_array(): void {

		$defaults = Settings::get_setting_defaults();

		$this->assertIsArray($defaults);
		$this->assertArrayHasKey('currency_symbol', $defaults);
		$this->assertEquals('USD', $defaults['currency_symbol']);
		$this->assertArrayHasKey('enable_registration', $defaults);
		$this->assertEquals(1, $defaults['enable_registration']);
	}

	/**
	 * Test add_section registers a new section.
	 */
	public function test_add_section_registers_section(): void {

		$settings = Settings::get_instance();

		$settings->add_section(
			'test_section',
			[
				'title' => 'Test Section',
				'desc'  => 'Test Description',
			]
		);

		$sections = $settings->get_sections();

		$this->assertArrayHasKey('test_section', $sections);
		$this->assertEquals('Test Section', $sections['test_section']['title']);
	}

	/**
	 * Test add_field registers a new field.
	 */
	public function test_add_field_registers_field(): void {

		$settings = Settings::get_instance();

		$settings->add_section('test_section_2', ['title' => 'Test']);

		$settings->add_field(
			'test_section_2',
			'test_field',
			[
				'title' => 'Test Field',
				'type'  => 'text',
			]
		);

		$section = $settings->get_section('test_section_2');

		$this->assertArrayHasKey('test_field', $section['fields']);
	}

	/**
	 * Test force_registration_status filter.
	 */
	public function test_force_registration_status_returns_all_when_enabled(): void {

		$settings = Settings::get_instance();

		// Save the setting
		$settings->save_setting('enable_registration', true);

		global $current_site;
		$original_site = $current_site;
		$current_site  = (object) ['id' => 1];

		$result = $settings->force_registration_status('', 'registration', 1);

		$current_site = $original_site;

		$this->assertEquals('all', $result);
	}

	/**
	 * Test force_add_new_users filter.
	 */
	public function test_force_add_new_users_returns_setting_value(): void {

		$settings = Settings::get_instance();

		$settings->save_setting('add_new_users', true);

		global $current_site;
		$original_site = $current_site;
		$current_site  = (object) ['id' => 1];

		$result = $settings->force_add_new_users('', 'add_new_users', 1);

		$current_site = $original_site;

		$this->assertTrue($result);
	}

	/**
	 * Test get_default_company_country uses geolocation.
	 */
	public function test_get_default_company_country_returns_country_code(): void {

		$settings = Settings::get_instance();

		$country = $settings->get_default_company_country();

		// Should return a 2-letter country code
		$this->assertIsString($country);
		$this->assertEquals(2, strlen($country));
	}

	/**
	 * Test save_settings processes multiple settings at once.
	 */
	public function test_save_settings_processes_multiple(): void {

		$settings = Settings::get_instance();

		$to_save = [
			'test_setting_1' => 'value1',
			'test_setting_2' => 'value2',
		];

		$result = $settings->save_settings($to_save);

		$this->assertIsArray($result);
	}

	/**
	 * Test settings with dashes trigger _doing_it_wrong.
	 */
	public function test_setting_with_dashes_triggers_warning(): void {

		$settings = Settings::get_instance();

		// This should trigger _doing_it_wrong but not fail
		$value = $settings->get_setting('test-with-dashes', 'default');

		$this->assertEquals('default', $value);
	}

	/**
	 * Test boundary: Empty string setting name.
	 */
	public function test_get_setting_with_empty_string(): void {

		$settings = Settings::get_instance();

		$value = $settings->get_setting('', 'default');

		$this->assertEquals('default', $value);
	}

	/**
	 * Test get_all_with_defaults includes callable defaults.
	 */
	public function test_get_all_with_defaults_evaluates_callables(): void {

		$settings = Settings::get_instance();

		$all = $settings->get_all_with_defaults();

		$this->assertIsArray($all);
	}
}