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
        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(49.031526, 2.060164));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals('6', $result->getStreetNumber());
        $this->assertEquals('Quai de la Tourelle', $result->getStreetName());
        $this->assertEquals('95000', $result->getPostalCode());
        $this->assertEquals('Cergy', $result->getLocality());
        $this->assertCount(3, $result->getAdminLevels());
        $this->assertEquals('Île-de-France', $result->getAdminLevels()->get(2)->getName());
        $this->assertEquals('Val-d\'Oise', $result->getAdminLevels()->get(3)->getName());
        $this->assertEquals('95', $result->getAdminLevels()->get(3)->getCode());
        $this->assertEquals('Cergy', $result->getAdminLevels()->get(4)->getName());
        $this->assertEquals('95127', $result->getAdminLevels()->get(4)->getCode());
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
        $this->assertEqualsWithDelta(49.031526, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(2.060164, $result->getCoordinates()->getLongitude(), 0.00001);
        $this->assertEquals('6', $result->getStreetNumber());
        $this->assertEquals('Quai de la Tourelle', $result->getStreetName());
        $this->assertEquals('95000', $result->getPostalCode());
        $this->assertEquals('Cergy', $result->getLocality());
        $this->assertCount(3, $result->getAdminLevels());
        $this->assertEquals('Île-de-France', $result->getAdminLevels()->get(2)->getName());
        $this->assertEquals('Val-d\'Oise', $result->getAdminLevels()->get(3)->getName());
        $this->assertEquals('95', $result->getAdminLevels()->get(3)->getCode());
        $this->assertEquals('Cergy', $result->getAdminLevels()->get(4)->getName());
        $this->assertEquals('95127', $result->getAdminLevels()->get(4)->getCode());
    }

    public function testGeocodeOnlyCityQuery()
    {
        $provider = Addok::withBANServer($this->getHttpClient());
        $results = $provider->geocodeQuery(GeocodeQuery::create('Grenoble'));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);

        /** @var \Geocoder\Model\Address $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEqualsWithDelta(45.182828, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(5.724369, $result->getCoordinates()->getLongitude(), 0.00001);
        $this->assertNull($result->getStreetNumber());
        $this->assertNull($result->getStreetName());
        $this->assertEquals('38000', $result->getPostalCode());
        $this->assertEquals('Grenoble', $result->getLocality());
        $this->assertCount(3, $result->getAdminLevels());
        $this->assertEquals('Auvergne-Rhône-Alpes', $result->getAdminLevels()->get(2)->getName());
        $this->assertEquals('Isère', $result->getAdminLevels()->get(3)->getName());
        $this->assertEquals('38', $result->getAdminLevels()->get(3)->getCode());
        $this->assertEquals('Grenoble', $result->getAdminLevels()->get(4)->getName());
        $this->assertEquals('38185', $result->getAdminLevels()->get(4)->getCode());
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
        $this->assertCount(4, $result->getAdminLevels());
        $this->assertEquals('Île-de-France', $result->getAdminLevels()->get(2)->getName());
        $this->assertEquals('Paris', $result->getAdminLevels()->get(3)->getName());
        $this->assertEquals('75', $result->getAdminLevels()->get(3)->getCode());
        $this->assertEquals('Paris', $result->getAdminLevels()->get(4)->getName());
        $this->assertEquals('75116', $result->getAdminLevels()->get(4)->getCode());
        $this->assertEquals('Paris 16e Arrondissement', $result->getAdminLevels()->get(5)->getName());
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
        $this->assertCount(4, $result->getAdminLevels());
        $this->assertEquals('Île-de-France', $result->getAdminLevels()->get(2)->getName());
        $this->assertEquals('Paris', $result->getAdminLevels()->get(3)->getName());
        $this->assertEquals('75', $result->getAdminLevels()->get(3)->getCode());
        $this->assertEquals('Paris', $result->getAdminLevels()->get(4)->getName());
        $this->assertEquals('75116', $result->getAdminLevels()->get(4)->getCode());
        $this->assertEquals('Paris 16e Arrondissement', $result->getAdminLevels()->get(5)->getName());
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
        $this->assertEqualsWithDelta(43.631962, $result->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(1.380094, $result->getCoordinates()->getLongitude(), 0.00001);
        $this->assertEquals('31700', $result->getPostalCode());
        $this->assertEquals('Blagnac', $result->getLocality());
    }
}
