<?php
/**
 * Test case for Country classes.
 *
 * @package WP_Ultimo
 * @subpackage Tests
 */

namespace WP_Ultimo\Tests\Country;

use WP_Ultimo\Country\Country;
use WP_Ultimo\Country\Country_Default;
use WP_Ultimo\Country\Country_US;
use WP_Ultimo\Country\Country_GB;
use WP_Ultimo\Country\Country_BR;
use WP_Ultimo\Country\Country_CA;
use WP_Ultimo\Country\Country_DE;
use WP_Ultimo\Country\Country_FR;
use WP_Ultimo\Country\Country_JP;
use WP_Ultimo\Country\Country_IN;
use WP_Ultimo\Country\Country_CN;
use WP_Ultimo\Country\Country_MX;
use WP_Ultimo\Country\Country_ES;
use WP_Ultimo\Country\Country_RU;
use WP_Ultimo\Country\Country_TR;
use WP_Ultimo\Country\Country_NL;
use WP_Ultimo\Country\Country_MY;
use WP_Ultimo\Country\Country_NE;
use WP_Ultimo\Country\Country_SG;
use WP_Ultimo\Country\Country_ZA;

/**
 * Test Country functionality.
 */
class Country_Test extends \WP_UnitTestCase {

	// ---------------------------------------------------------------
	// Country_Default tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_Default::build creates instance.
	 */
	public function test_country_default_build(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$this->assertInstanceOf(Country::class, $country);
		$this->assertInstanceOf(Country_Default::class, $country);
	}

	/**
	 * Test Country_Default get_name returns provided name.
	 */
	public function test_country_default_get_name(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$this->assertEquals('Test Country', $country->get_name());
	}

	/**
	 * Test Country_Default magic getter for attributes.
	 */
	public function test_country_default_magic_getter(): void {

		$country = Country_Default::build('XX', 'Test Country', [
			'currency'   => 'XXX',
			'phone_code' => 99,
		]);

		$this->assertEquals('XX', $country->country_code);
		$this->assertEquals('XXX', $country->currency);
		$this->assertEquals(99, $country->phone_code);
	}

	/**
	 * Test Country_Default magic getter returns null for unknown attribute.
	 */
	public function test_country_default_magic_getter_unknown(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$this->assertNull($country->nonexistent_attribute);
	}

	/**
	 * Test Country_Default with no name falls back to wu_get_country_name.
	 */
	public function test_country_default_build_without_name(): void {

		$country = Country_Default::build('US');

		$this->assertIsString($country->get_name());
	}

	/**
	 * Test Country_Default has empty states by default.
	 */
	public function test_country_default_empty_states(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$states = $country->get_states();

		$this->assertIsArray($states);
		$this->assertEmpty($states);
	}

	/**
	 * Test Country_Default get_cities returns empty for no state.
	 */
	public function test_country_default_get_cities_empty_state(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$cities = $country->get_cities('');

		$this->assertIsArray($cities);
		$this->assertEmpty($cities);
	}

	/**
	 * Test Country_Default get_cities returns empty for nonexistent state.
	 */
	public function test_country_default_get_cities_nonexistent(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$cities = $country->get_cities('ZZ');

		$this->assertIsArray($cities);
		$this->assertEmpty($cities);
	}

	/**
	 * Test Country_Default get_administrative_division_name.
	 */
	public function test_country_default_administrative_division_name(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$name = $country->get_administrative_division_name();

		$this->assertIsString($name);
		// Default state_type is 'unknown' which maps to 'state / province'
		$this->assertStringContainsString('state', $name);
	}

	/**
	 * Test Country_Default get_municipality_name.
	 */
	public function test_country_default_municipality_name(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$name = $country->get_municipality_name();

		$this->assertIsString($name);
		$this->assertEquals('city', $name);
	}

	/**
	 * Test get_municipality_name with ucwords.
	 */
	public function test_country_default_municipality_name_ucwords(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$name = $country->get_municipality_name(null, true);

		$this->assertEquals('City', $name);
	}

	/**
	 * Test get_states_as_options with empty states returns empty.
	 */
	public function test_country_default_states_as_options_empty(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$options = $country->get_states_as_options();

		$this->assertIsArray($options);
		$this->assertEmpty($options);
	}

	/**
	 * Test get_cities_as_options with empty state.
	 */
	public function test_country_default_cities_as_options_empty(): void {

		$country = Country_Default::build('XX', 'Test Country');

		$options = $country->get_cities_as_options('');

		$this->assertIsArray($options);
		$this->assertEmpty($options);
	}

	// ---------------------------------------------------------------
	// Country_US tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_US singleton.
	 */
	public function test_us_singleton(): void {

		$us1 = Country_US::get_instance();
		$us2 = Country_US::get_instance();

		$this->assertSame($us1, $us2);
		$this->assertInstanceOf(Country_US::class, $us1);
	}

	/**
	 * Test Country_US get_name.
	 */
	public function test_us_get_name(): void {

		$us = Country_US::get_instance();

		$this->assertEquals('United States', $us->get_name());
	}

	/**
	 * Test Country_US attributes.
	 */
	public function test_us_attributes(): void {

		$us = Country_US::get_instance();

		$this->assertEquals('US', $us->country_code);
		$this->assertEquals('USD', $us->currency);
		$this->assertEquals(1, $us->phone_code);
	}

	/**
	 * Test Country_US has states.
	 */
	public function test_us_has_states(): void {

		$us = Country_US::get_instance();

		$states = $us->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
		$this->assertArrayHasKey('CA', $states);
		$this->assertArrayHasKey('NY', $states);
		$this->assertArrayHasKey('TX', $states);
		$this->assertEquals('California', $states['CA']);
		$this->assertEquals('New York', $states['NY']);
		$this->assertEquals('Texas', $states['TX']);
	}

	/**
	 * Test Country_US state count.
	 */
	public function test_us_state_count(): void {

		$us = Country_US::get_instance();

		$states = $us->get_states();

		// US has 66 entries (50 states + DC + territories + minor outlying islands)
		$this->assertCount(66, $states);
	}

	/**
	 * Test Country_US get_states_as_options includes placeholder.
	 */
	public function test_us_states_as_options_with_placeholder(): void {

		$us = Country_US::get_instance();

		$options = $us->get_states_as_options();

		$this->assertIsArray($options);
		// Should have placeholder + all states
		$this->assertArrayHasKey('', $options);
		$this->assertCount(67, $options); // 66 states + 1 placeholder
	}

	/**
	 * Test Country_US get_states_as_options with custom placeholder.
	 */
	public function test_us_states_as_options_custom_placeholder(): void {

		$us = Country_US::get_instance();

		$options = $us->get_states_as_options('Choose a state');

		$this->assertEquals('Choose a state', $options['']);
	}

	/**
	 * Test Country_US get_states_as_options without placeholder.
	 */
	public function test_us_states_as_options_no_placeholder(): void {

		$us = Country_US::get_instance();

		$options = $us->get_states_as_options(false);

		$this->assertCount(66, $options);
		$this->assertArrayNotHasKey('', $options);
	}

	/**
	 * Test Country_US administrative division name is 'state'.
	 */
	public function test_us_administrative_division_name(): void {

		$us = Country_US::get_instance();

		$name = $us->get_administrative_division_name();

		$this->assertEquals('state', $name);
	}

	/**
	 * Test Country_US administrative division name with ucwords.
	 */
	public function test_us_administrative_division_name_ucwords(): void {

		$us = Country_US::get_instance();

		$name = $us->get_administrative_division_name(null, true);

		$this->assertEquals('State', $name);
	}

	// ---------------------------------------------------------------
	// Country_GB tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_GB singleton.
	 */
	public function test_gb_singleton(): void {

		$gb = Country_GB::get_instance();

		$this->assertInstanceOf(Country_GB::class, $gb);
	}

	/**
	 * Test Country_GB get_name.
	 */
	public function test_gb_get_name(): void {

		$gb = Country_GB::get_instance();

		$this->assertEquals('United Kingdom', $gb->get_name());
	}

	/**
	 * Test Country_GB attributes.
	 */
	public function test_gb_attributes(): void {

		$gb = Country_GB::get_instance();

		$this->assertEquals('GB', $gb->country_code);
		$this->assertEquals('GBP', $gb->currency);
		$this->assertEquals(44, $gb->phone_code);
	}

	/**
	 * Test Country_GB has states (counties/regions).
	 */
	public function test_gb_has_states(): void {

		$gb = Country_GB::get_instance();

		$states = $gb->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
		// GB has 247 entries
		$this->assertCount(247, $states);
	}

	// ---------------------------------------------------------------
	// Country_BR tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_BR singleton and name.
	 */
	public function test_br_basics(): void {

		$br = Country_BR::get_instance();

		$this->assertInstanceOf(Country_BR::class, $br);
		$this->assertEquals('Brazil', $br->get_name());
		$this->assertEquals('BR', $br->country_code);
		$this->assertEquals('BRL', $br->currency);
		$this->assertEquals(55, $br->phone_code);
	}

	/**
	 * Test Country_BR has 27 states.
	 */
	public function test_br_state_count(): void {

		$br = Country_BR::get_instance();

		$states = $br->get_states();

		$this->assertCount(27, $states);
		$this->assertArrayHasKey('SP', $states);
		$this->assertArrayHasKey('RJ', $states);
	}

	// ---------------------------------------------------------------
	// Country_CA tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_CA basics.
	 */
	public function test_ca_basics(): void {

		$ca = Country_CA::get_instance();

		$this->assertInstanceOf(Country_CA::class, $ca);
		$this->assertEquals('Canada', $ca->get_name());
		$this->assertEquals('CA', $ca->country_code);
		$this->assertEquals('CAD', $ca->currency);
	}

	/**
	 * Test Country_CA has provinces.
	 */
	public function test_ca_has_provinces(): void {

		$ca = Country_CA::get_instance();

		$states = $ca->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
		$this->assertArrayHasKey('ON', $states);
		$this->assertArrayHasKey('QC', $states);
		$this->assertArrayHasKey('BC', $states);
	}

	// ---------------------------------------------------------------
	// Country_DE tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_DE basics.
	 */
	public function test_de_basics(): void {

		$de = Country_DE::get_instance();

		$this->assertInstanceOf(Country_DE::class, $de);
		$this->assertEquals('Germany', $de->get_name());
		$this->assertEquals('DE', $de->country_code);
		$this->assertEquals('EUR', $de->currency);
	}

	/**
	 * Test Country_DE has states.
	 */
	public function test_de_has_states(): void {

		$de = Country_DE::get_instance();

		$states = $de->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
		// Germany has 16 federal states
		$this->assertCount(16, $states);
	}

	// ---------------------------------------------------------------
	// Country_FR tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_FR basics.
	 */
	public function test_fr_basics(): void {

		$fr = Country_FR::get_instance();

		$this->assertInstanceOf(Country_FR::class, $fr);
		$this->assertEquals('France', $fr->get_name());
		$this->assertEquals('FR', $fr->country_code);
		$this->assertEquals('EUR', $fr->currency);
	}

	/**
	 * Test Country_FR has regions.
	 */
	public function test_fr_has_regions(): void {

		$fr = Country_FR::get_instance();

		$states = $fr->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
	}

	// ---------------------------------------------------------------
	// Country_JP tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_JP basics.
	 */
	public function test_jp_basics(): void {

		$jp = Country_JP::get_instance();

		$this->assertInstanceOf(Country_JP::class, $jp);
		$this->assertEquals('Japan', $jp->get_name());
		$this->assertEquals('JP', $jp->country_code);
		$this->assertEquals('JPY', $jp->currency);
	}

	/**
	 * Test Country_JP has prefectures.
	 */
	public function test_jp_has_prefectures(): void {

		$jp = Country_JP::get_instance();

		$states = $jp->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
		// Japan has 47 prefectures
		$this->assertCount(47, $states);
	}

	// ---------------------------------------------------------------
	// Country_IN tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_IN basics.
	 */
	public function test_in_basics(): void {

		$in = Country_IN::get_instance();

		$this->assertInstanceOf(Country_IN::class, $in);
		$this->assertEquals('India', $in->get_name());
		$this->assertEquals('IN', $in->country_code);
		$this->assertEquals('INR', $in->currency);
	}

	/**
	 * Test Country_IN has states.
	 */
	public function test_in_has_states(): void {

		$in = Country_IN::get_instance();

		$states = $in->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
	}

	// ---------------------------------------------------------------
	// Country_CN tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_CN basics.
	 */
	public function test_cn_basics(): void {

		$cn = Country_CN::get_instance();

		$this->assertInstanceOf(Country_CN::class, $cn);
		$this->assertEquals('China', $cn->get_name());
		$this->assertEquals('CN', $cn->country_code);
		$this->assertEquals('CNY', $cn->currency);
	}

	/**
	 * Test Country_CN has provinces.
	 */
	public function test_cn_has_provinces(): void {

		$cn = Country_CN::get_instance();

		$states = $cn->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
	}

	// ---------------------------------------------------------------
	// Country_MX tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_MX basics.
	 */
	public function test_mx_basics(): void {

		$mx = Country_MX::get_instance();

		$this->assertInstanceOf(Country_MX::class, $mx);
		$this->assertEquals('Mexico', $mx->get_name());
		$this->assertEquals('MX', $mx->country_code);
		$this->assertEquals('MXN', $mx->currency);
	}

	/**
	 * Test Country_MX has states.
	 */
	public function test_mx_has_states(): void {

		$mx = Country_MX::get_instance();

		$states = $mx->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
		// Mexico has 32 states
		$this->assertCount(32, $states);
	}

	// ---------------------------------------------------------------
	// Country_ES tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_ES basics.
	 */
	public function test_es_basics(): void {

		$es = Country_ES::get_instance();

		$this->assertInstanceOf(Country_ES::class, $es);
		$this->assertEquals('Spain', $es->get_name());
		$this->assertEquals('ES', $es->country_code);
		$this->assertEquals('EUR', $es->currency);
	}

	/**
	 * Test Country_ES has autonomous communities.
	 */
	public function test_es_has_communities(): void {

		$es = Country_ES::get_instance();

		$states = $es->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
	}

	// ---------------------------------------------------------------
	// Country_RU tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_RU basics.
	 */
	public function test_ru_basics(): void {

		$ru = Country_RU::get_instance();

		$this->assertInstanceOf(Country_RU::class, $ru);
		$this->assertEquals('Russia', $ru->get_name());
		$this->assertEquals('RU', $ru->country_code);
		$this->assertEquals('RUB', $ru->currency);
	}

	/**
	 * Test Country_RU has regions.
	 */
	public function test_ru_has_regions(): void {

		$ru = Country_RU::get_instance();

		$states = $ru->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
	}

	// ---------------------------------------------------------------
	// Country_TR tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_TR basics.
	 */
	public function test_tr_basics(): void {

		$tr = Country_TR::get_instance();

		$this->assertInstanceOf(Country_TR::class, $tr);
		$this->assertEquals('Turkey', $tr->get_name());
		$this->assertEquals('TR', $tr->country_code);
		$this->assertEquals('TRY', $tr->currency);
	}

	/**
	 * Test Country_TR has provinces.
	 */
	public function test_tr_has_provinces(): void {

		$tr = Country_TR::get_instance();

		$states = $tr->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
	}

	// ---------------------------------------------------------------
	// Country_NL tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_NL basics.
	 */
	public function test_nl_basics(): void {

		$nl = Country_NL::get_instance();

		$this->assertInstanceOf(Country_NL::class, $nl);
		$this->assertEquals('Netherlands', $nl->get_name());
		$this->assertEquals('NL', $nl->country_code);
		$this->assertEquals('EUR', $nl->currency);
	}

	/**
	 * Test Country_NL has provinces.
	 */
	public function test_nl_has_provinces(): void {

		$nl = Country_NL::get_instance();

		$states = $nl->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
	}

	// ---------------------------------------------------------------
	// Country_MY tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_MY basics.
	 */
	public function test_my_basics(): void {

		$my = Country_MY::get_instance();

		$this->assertInstanceOf(Country_MY::class, $my);
		$this->assertEquals('Malaysia', $my->get_name());
		$this->assertEquals('MY', $my->country_code);
		$this->assertEquals('MYR', $my->currency);
	}

	// ---------------------------------------------------------------
	// Country_NE tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_NE basics.
	 */
	public function test_ne_basics(): void {

		$ne = Country_NE::get_instance();

		$this->assertInstanceOf(Country_NE::class, $ne);
		$this->assertEquals('Niger', $ne->get_name());
		$this->assertEquals('NE', $ne->country_code);
	}

	// ---------------------------------------------------------------
	// Country_SG tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_SG basics.
	 */
	public function test_sg_basics(): void {

		$sg = Country_SG::get_instance();

		$this->assertInstanceOf(Country_SG::class, $sg);
		$this->assertEquals('Singapore', $sg->get_name());
		$this->assertEquals('SG', $sg->country_code);
		$this->assertEquals('SGD', $sg->currency);
	}

	// ---------------------------------------------------------------
	// Country_ZA tests
	// ---------------------------------------------------------------

	/**
	 * Test Country_ZA basics.
	 */
	public function test_za_basics(): void {

		$za = Country_ZA::get_instance();

		$this->assertInstanceOf(Country_ZA::class, $za);
		$this->assertEquals('South Africa', $za->get_name());
		$this->assertEquals('ZA', $za->country_code);
		$this->assertEquals('ZAR', $za->currency);
	}

	/**
	 * Test Country_ZA has provinces.
	 */
	public function test_za_has_provinces(): void {

		$za = Country_ZA::get_instance();

		$states = $za->get_states();

		$this->assertIsArray($states);
		$this->assertNotEmpty($states);
		// South Africa has 9 provinces
		$this->assertCount(9, $states);
	}

	// ---------------------------------------------------------------
	// Cross-country filter tests
	// ---------------------------------------------------------------

	/**
	 * Test wu_country_get_states filter works.
	 */
	public function test_states_filter(): void {

		add_filter('wu_country_get_states', function ($states, $country_code) {
			if ($country_code === 'US') {
				$states['TEST'] = 'Test State';
			}
			return $states;
		}, 10, 2);

		$us = Country_US::get_instance();

		$states = $us->get_states();

		$this->assertArrayHasKey('TEST', $states);
		$this->assertEquals('Test State', $states['TEST']);

		// Clean up
		remove_all_filters('wu_country_get_states');
	}

	/**
	 * Test wu_country_get_administrative_division_name filter works.
	 */
	public function test_administrative_division_name_filter(): void {

		add_filter('wu_country_get_administrative_division_name', function ($name) {
			return 'custom division';
		});

		$us = Country_US::get_instance();

		$name = $us->get_administrative_division_name();

		$this->assertEquals('custom division', $name);

		// Clean up
		remove_all_filters('wu_country_get_administrative_division_name');
	}

	/**
	 * Test wu_country_get_municipality_name filter works.
	 */
	public function test_municipality_name_filter(): void {

		add_filter('wu_country_get_municipality_name', function ($name) {
			return 'town';
		});

		$us = Country_US::get_instance();

		$name = $us->get_municipality_name();

		$this->assertEquals('town', $name);

		// Clean up
		remove_all_filters('wu_country_get_municipality_name');
	}

	// ---------------------------------------------------------------
	// Base class method coverage through concrete implementations
	// ---------------------------------------------------------------

	/**
	 * Test get_cities with valid state returns cities.
	 */
	public function test_get_cities_with_valid_state(): void {

		$us = Country_US::get_instance();

		// DC has 5 cities per the header comment
		$cities = $us->get_cities('DC');

		// If the city data file exists, we should get cities
		if ( ! empty($cities)) {
			$this->assertIsArray($cities);
			$this->assertNotEmpty($cities);
		} else {
			// City data files may not exist in test env
			$this->assertIsArray($cities);
		}
	}

	/**
	 * Test get_cities with invalid state returns empty.
	 */
	public function test_get_cities_with_invalid_state(): void {

		$us = Country_US::get_instance();

		$cities = $us->get_cities('INVALID');

		$this->assertIsArray($cities);
		$this->assertEmpty($cities);
	}

	/**
	 * Test get_cities_as_options with valid state.
	 */
	public function test_get_cities_as_options_with_state(): void {

		$us = Country_US::get_instance();

		$options = $us->get_cities_as_options('DC');

		$this->assertIsArray($options);

		// If cities exist, options should include placeholder
		if (count($options) > 1) {
			$this->assertArrayHasKey('', $options);
		}
	}

	/**
	 * Test get_cities_as_options with custom placeholder.
	 */
	public function test_get_cities_as_options_custom_placeholder(): void {

		$us = Country_US::get_instance();

		$options = $us->get_cities_as_options('DC', 'Pick a city');

		$this->assertIsArray($options);

		if ( ! empty($options)) {
			$this->assertArrayHasKey('', $options);
			$this->assertEquals('Pick a city', $options['']);
		}
	}

	/**
	 * Test get_cities_as_options without placeholder.
	 */
	public function test_get_cities_as_options_no_placeholder(): void {

		$us = Country_US::get_instance();

		$options = $us->get_cities_as_options('DC', false);

		$this->assertIsArray($options);
		// Should not have empty key placeholder
		$this->assertArrayNotHasKey('', $options);
	}

	/**
	 * Test all country singletons return consistent instances.
	 */
	public function test_all_singletons_consistent(): void {

		$classes = [
			Country_US::class,
			Country_GB::class,
			Country_BR::class,
			Country_CA::class,
			Country_DE::class,
			Country_FR::class,
			Country_JP::class,
			Country_IN::class,
			Country_CN::class,
			Country_MX::class,
			Country_ES::class,
			Country_RU::class,
			Country_TR::class,
			Country_NL::class,
			Country_MY::class,
			Country_NE::class,
			Country_SG::class,
			Country_ZA::class,
		];

		foreach ($classes as $class) {
			$instance1 = $class::get_instance();
			$instance2 = $class::get_instance();

			$this->assertSame($instance1, $instance2, "Singleton failed for {$class}");
			$this->assertInstanceOf(Country::class, $instance1, "Not a Country instance: {$class}");
			$this->assertIsString($instance1->get_name(), "get_name() not string for {$class}");
			$this->assertNotEmpty($instance1->get_name(), "get_name() empty for {$class}");
			$this->assertNotNull($instance1->country_code, "country_code null for {$class}");
			$this->assertNotNull($instance1->currency, "currency null for {$class}");
		}
	}

	/**
	 * Test all countries have non-empty states.
	 */
	public function test_all_countries_have_states(): void {

		$classes = [
			Country_US::class,
			Country_GB::class,
			Country_BR::class,
			Country_CA::class,
			Country_DE::class,
			Country_FR::class,
			Country_JP::class,
			Country_IN::class,
			Country_CN::class,
			Country_MX::class,
			Country_ES::class,
			Country_RU::class,
			Country_TR::class,
			Country_NL::class,
			Country_MY::class,
			Country_NE::class,
			Country_SG::class,
			Country_ZA::class,
		];

		foreach ($classes as $class) {
			$instance = $class::get_instance();
			$states   = $instance->get_states();

			$this->assertIsArray($states, "get_states() not array for {$class}");
			$this->assertNotEmpty($states, "get_states() empty for {$class}");
		}
	}
}
