<?php

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\Addok\Tests;

use Geocoder\IntegrationTest\ProviderIntegrationTest;
use Geocoder\Provider\Addok\Addok;
use Psr\Http\Client\ClientInterface;

class IntegrationTest extends ProviderIntegrationTest
{
    protected $testAddress = true;

    protected $testReverse = true;

    protected $testIpv4 = false;

    protected $testIpv6 = false;

    protected $skippedTests = [
        'testGeocodeQuery'              => 'BAN Server supports France only.',
        'testReverseQuery'              => 'BAN Server supports France only.',
        'testReverseQueryWithNoResults' => 'Addok returns "debug" information for reverse geocoding on coordinates 0, 0. See https://github.com/addok/addok/issues/505',
    ];

    protected function createProvider(ClientInterface $httpClient)
    {
        return Addok::withBANServer($httpClient);
    }

    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    protected function getApiKey()
    {
    }
}
