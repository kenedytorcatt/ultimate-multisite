<?php
/**
 * Tests for color helper functions.
 *
 * @package WP_Ultimo\Tests
 */

namespace WP_Ultimo\Functions;

use WP_UnitTestCase;
use Mexitek\PHPColors\Color;

/**
 * Test class for color helper functions.
 */
class Color_Helpers_Test extends WP_UnitTestCase {

	/**
	 * Test wu_color returns Color object.
	 */
	public function test_color_returns_color_object(): void {
		$color = wu_color('#000000');
		$this->assertInstanceOf(Color::class, $color);
	}

	/**
	 * Test wu_color with valid hex colors.
	 */
	public function test_color_with_valid_hex(): void {
		$black = wu_color('#000000');
		$this->assertEquals('000000', $black->getHex());

		$white = wu_color('#ffffff');
		$this->assertEquals('ffffff', $white->getHex());

		$red = wu_color('#ff0000');
		$this->assertEquals('ff0000', $red->getHex());
	}

	/**
	 * Test wu_color with shorthand hex.
	 */
	public function test_color_with_shorthand_hex(): void {
		$color = wu_color('#fff');
		$this->assertInstanceOf(Color::class, $color);
	}

	/**
	 * Test wu_color with invalid hex returns default.
	 */
	public function test_color_with_invalid_hex(): void {
		$color = wu_color('invalid');
		$this->assertInstanceOf(Color::class, $color);
		// Should return the fallback color #f9f9f9
		$this->assertEquals('f9f9f9', $color->getHex());
	}

	/**
	 * Test wu_color can manipulate colors.
	 */
	public function test_color_manipulation(): void {
		$color = wu_color('#336699');

		// Test darken
		$darker = $color->darken(10);
		$this->assertNotEquals($color->getHex(), $darker);

		// Test lighten
		$lighter = $color->lighten(10);
		$this->assertNotEquals($color->getHex(), $lighter);
	}

	/**
	 * Test wu_get_random_color returns valid color class.
	 */
	public function test_get_random_color_returns_string(): void {
		$color = wu_get_random_color(0);
		$this->assertIsString($color);
		$this->assertStringContainsString('wu-bg-', $color);
	}

	/**
	 * Test wu_get_random_color with different indices.
	 */
	public function test_get_random_color_with_indices(): void {
		$color0 = wu_get_random_color(0);
		$color1 = wu_get_random_color(1);
		$color2 = wu_get_random_color(2);

		$this->assertEquals('wu-bg-red-500', $color0);
		$this->assertEquals('wu-bg-green-500', $color1);
		$this->assertEquals('wu-bg-blue-500', $color2);
	}

	/**
	 * Test wu_get_random_color with out of bounds index.
	 */
	public function test_get_random_color_out_of_bounds(): void {
		$color = wu_get_random_color(999);
		$this->assertIsString($color);
		$this->assertStringContainsString('wu-bg-', $color);
		// Should return a random color when index is out of bounds
		$this->assertMatchesRegularExpression('/wu-bg-(red|green|blue|yellow|orange|purple|pink)-500/', $color);
	}

	/**
	 * Test wu_get_random_color with negative index.
	 */
	public function test_get_random_color_negative_index(): void {
		$color = wu_get_random_color(-1);
		$this->assertIsString($color);
		$this->assertStringContainsString('wu-bg-', $color);
	}
}
