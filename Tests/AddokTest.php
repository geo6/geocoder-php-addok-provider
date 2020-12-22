<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\Addok\Tests;

use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\Provider\Addok\Addok;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

class AddokTest extends BaseTestCase
{
    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    public function testGeocodeWithLocalhostIPv4()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The Addok provider does not support IP addresses, only street addresses.');

        $provider = Addok::withBANServer($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    public function testGeocodeWithLocalhostIPv6()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The Addok provider does not support IP addresses, only street addresses.');

        $provider = Addok::withBANServer($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('::1'));
    }

    public function testGeocodeWithRealIPv6()
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The Addok provider does not support IP addresses, only street addresses.');

        $provider = Addok::withBANServer($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('::ffff:88.188.221.14'));
    }

    public function testReverseQuery()
    {
        $provider = Addok::withBANServer($this->getHttpClient());
        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(49.031407, 2.060204));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals('6', $result->getStreetNumber());
        $this->assertEquals('Quai de la Tourelle', $result->getStreetName());
        $this->assertEquals('95000', $result->getPostalCode());
        $this->assertEquals('Cergy', $result->getLocality());
    }

    public function testGeocodeQuery()
    {
        $provider = Addok::withBANServer($this->getHttpClient());
        $results = $provider->geocodeQuery(GeocodeQuery::create('6 quai de la tourelle cergy'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEqualsWithDelta(49.031407, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(2.060204, $result->getCoordinates()->getLongitude(), 0.00001);
        $this->assertEquals('6', $result->getStreetNumber());
        $this->assertEquals('Quai de la Tourelle', $result->getStreetName());
        $this->assertEquals('95000', $result->getPostalCode());
        $this->assertEquals('Cergy', $result->getLocality());
    }

    public function testGeocodeOnlyCityQuery()
    {
        $provider = Addok::withBANServer($this->getHttpClient());
        $results = $provider->geocodeQuery(GeocodeQuery::create('Meaux'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEqualsWithDelta(48.95732, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(2.902793, $result->getCoordinates()->getLongitude(), 0.00001);
        $this->assertNull($result->getStreetNumber());
        $this->assertNull($result->getStreetName());
        $this->assertEquals('77100', $result->getPostalCode());
        $this->assertEquals('Meaux', $result->getLocality());
    }

    public function testGeocodeHouseNumberTypeQuery()
    {
        $provider = Addok::withBANServer($this->getHttpClient());
        $results = $provider->geocodeQuery(
            GeocodeQuery::create('20 avenue Kléber, Paris')->withData('type', Addok::TYPE_HOUSENUMBER)
        );

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals('20', $result->getStreetNumber());
        $this->assertEquals('Avenue Kléber', $result->getStreetName());
        $this->assertEquals('75016', $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
    }

    public function testGeocodeStreetTypeQuery()
    {
        $provider = Addok::withBANServer($this->getHttpClient());
        $results = $provider->geocodeQuery(
            GeocodeQuery::create('20 avenue Kléber, Paris')->withData('type', Addok::TYPE_STREET)
        );

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertNull($result->getStreetNumber());
        $this->assertEquals('Avenue Kléber', $result->getStreetName());
        $this->assertEquals('75016', $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
    }

    public function testGeocodeLocalityQuery()
    {
        $provider = Addok::withBANServer($this->getHttpClient());
        $results = $provider->geocodeQuery(
            GeocodeQuery::create('20 avenue Kléber, Paris')->withData('type', Addok::TYPE_LOCALITY)
        );

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertNull($result->getStreetNumber());
        $this->assertNull($result->getStreetName());
        $this->assertEqualsWithDelta(48.871759, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(2.294253, $result->getCoordinates()->getLongitude(), 0.00001);
        $this->assertEquals('75016', $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
    }
}
