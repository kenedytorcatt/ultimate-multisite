<?php
/**
 * Extended tests for date functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;

/**
 * Test class for date functions - extended coverage.
 */
class Date_Functions_Extended_Test extends WP_UnitTestCase {

	/**
	 * Test wu_validate_date with valid date.
	 */
	public function test_validate_date_valid(): void {

		$this->assertTrue(wu_validate_date('2024-01-15 10:30:00'));
	}

	/**
	 * Test wu_validate_date with null returns true.
	 */
	public function test_validate_date_null(): void {

		$this->assertTrue(wu_validate_date(null));
	}

	/**
	 * Test wu_validate_date with false returns false.
	 */
	public function test_validate_date_false(): void {

		$this->assertFalse(wu_validate_date(false));
	}

	/**
	 * Test wu_validate_date with empty string returns false.
	 */
	public function test_validate_date_empty_string(): void {

		$this->assertFalse(wu_validate_date(''));
	}

	/**
	 * Test wu_validate_date with invalid date.
	 */
	public function test_validate_date_invalid(): void {

		$this->assertFalse(wu_validate_date('not-a-date'));
	}

	/**
	 * Test wu_validate_date with custom format.
	 */
	public function test_validate_date_custom_format(): void {

		$this->assertTrue(wu_validate_date('15/01/2024', 'd/m/Y'));
	}

	/**
	 * Test wu_date returns DateTime object.
	 */
	public function test_date_returns_datetime(): void {

		$result = wu_date('2024-01-15 10:30:00');

		$this->assertInstanceOf(\DateTime::class, $result);
	}

	/**
	 * Test wu_date with invalid date returns current time.
	 */
	public function test_date_invalid_returns_datetime(): void {

		$result = wu_date('invalid');

		$this->assertInstanceOf(\DateTime::class, $result);
	}

	/**
	 * Test wu_date with false returns current time.
	 */
	public function test_date_false_returns_datetime(): void {

		$result = wu_date(false);

		$this->assertInstanceOf(\DateTime::class, $result);
	}

	/**
	 * Test wu_get_days_ago returns integer.
	 */
	public function test_get_days_ago_returns_int(): void {

		$result = wu_get_days_ago('2024-01-01 00:00:00', '2024-01-10 00:00:00');

		$this->assertIsInt($result);
	}

	/**
	 * Test wu_get_days_ago with same date.
	 */
	public function test_get_days_ago_same_date(): void {

		$result = wu_get_days_ago('2024-01-01 00:00:00', '2024-01-01 00:00:00');

		$this->assertEquals(0, $result);
	}

	/**
	 * Test wu_get_current_time returns string.
	 */
	public function test_get_current_time_returns_string(): void {

		$result = wu_get_current_time('mysql');

		$this->assertIsString($result);
		$this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result);
	}

	/**
	 * Test wu_get_current_time with GMT.
	 */
	public function test_get_current_time_gmt(): void {

		$result = wu_get_current_time('mysql', true);

		$this->assertIsString($result);
		$this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result);
	}

	/**
	 * Test wu_filter_duration_unit with day singular.
	 */
	public function test_filter_duration_unit_day_singular(): void {

		$result = wu_filter_duration_unit('day', 1);

		$this->assertEquals('Day', $result);
	}

	/**
	 * Test wu_filter_duration_unit with day plural.
	 */
	public function test_filter_duration_unit_day_plural(): void {

		$result = wu_filter_duration_unit('day', 5);

		$this->assertEquals('Days', $result);
	}

	/**
	 * Test wu_filter_duration_unit with month singular.
	 */
	public function test_filter_duration_unit_month_singular(): void {

		$result = wu_filter_duration_unit('month', 1);

		$this->assertEquals('Month', $result);
	}

	/**
	 * Test wu_filter_duration_unit with month plural.
	 */
	public function test_filter_duration_unit_month_plural(): void {

		$result = wu_filter_duration_unit('month', 3);

		$this->assertEquals('Months', $result);
	}

	/**
	 * Test wu_filter_duration_unit with year singular.
	 */
	public function test_filter_duration_unit_year_singular(): void {

		$result = wu_filter_duration_unit('year', 1);

		$this->assertEquals('Year', $result);
	}

	/**
	 * Test wu_filter_duration_unit with year plural.
	 */
	public function test_filter_duration_unit_year_plural(): void {

		$result = wu_filter_duration_unit('year', 2);

		$this->assertEquals('Years', $result);
	}

	/**
	 * Test wu_filter_duration_unit with unknown unit.
	 */
	public function test_filter_duration_unit_unknown(): void {

		$result = wu_filter_duration_unit('unknown', 1);

		$this->assertEquals('', $result);
	}

	/**
	 * Test wu_human_time_diff returns string.
	 */
	public function test_human_time_diff_returns_string(): void {

		$result = wu_human_time_diff(gmdate('Y-m-d H:i:s'));

		$this->assertIsString($result);
	}

	/**
	 * Test wu_human_time_diff with old date shows date format.
	 */
	public function test_human_time_diff_old_date(): void {

		$old_date = gmdate('Y-m-d H:i:s', strtotime('-30 days'));

		$result = wu_human_time_diff($old_date);

		$this->assertIsString($result);
		$this->assertStringContainsString('on', $result);
	}

	/**
	 * Test wu_convert_php_date_format_to_moment_js_format.
	 */
	public function test_convert_date_format_to_moment(): void {

		$this->assertEquals('YYYY-MM-DD', wu_convert_php_date_format_to_moment_js_format('Y-m-d'));
	}

	/**
	 * Test wu_convert_php_date_format_to_moment_js_format with time.
	 */
	public function test_convert_date_format_to_moment_with_time(): void {

		$result = wu_convert_php_date_format_to_moment_js_format('Y-m-d H:i:s');

		$this->assertEquals('YYYY-MM-DD HH:mm:ss', $result);
	}

	/**
	 * Test wu_convert_php_date_format_to_moment_js_format with day name.
	 */
	public function test_convert_date_format_to_moment_day_name(): void {

		$result = wu_convert_php_date_format_to_moment_js_format('l, F j, Y');

		$this->assertEquals('dddd, MMMM D, YYYY', $result);
	}
}
