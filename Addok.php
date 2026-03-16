<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\Addok;

use Geocoder\Collection;
use Geocoder\Exception\InvalidArgument;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Psr\Http\Client\ClientInterface;

/**
 * @author Jonathan Beliën <jbe@geo6.be>
 */
final class Addok extends AbstractHttpProvider implements Provider
{
    const TYPE_HOUSENUMBER = 'housenumber';
    const TYPE_STREET = 'street';
    const TYPE_LOCALITY = 'locality';
    const TYPE_MUNICIPALITY = 'municipality';

    /**
     * @var string
     */
    private $rootUrl;

    /**
     * @param ClientInterface $client
     * @param string|null     $locale
     *
     * @return Addok
     */
    public static function withBANServer(ClientInterface $client)
    {
        return new self($client, 'https://data.geopf.fr/geocodage');
    }

    /**
     * @param ClientInterface $client  an HTTP adapter
     * @param string          $rootUrl Root URL of the addok server
     */
    public function __construct(ClientInterface $client, $rootUrl)
    {
        parent::__construct($client);

        $this->rootUrl = rtrim($rootUrl, '/');
    }

    private function getGeocodeEndpointUrl(): string
    {
        return $this->rootUrl.'/search/?q=%s&limit=%d&autocomplete=0';
    }

    private function getReverseEndpointUrl(): string
    {
        return $this->rootUrl.'/reverse/?lat=%F&lon=%F';
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        $address = $query->getText();
        // This API does not support IP
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The Addok provider does not support IP addresses, only street addresses.');
        }

        // Save a request if no valid address entered
        if (empty($address)) {
            throw new InvalidArgument('Address cannot be empty.');
        }

        $url = sprintf($this->getGeocodeEndpointUrl(), urlencode($address), $query->getLimit());

        if ($type = $query->getData('type', null)) {
            $url .= sprintf('&type=%s', $type);
        }

        $json = $this->executeQuery($url);

        // no result
        if (empty($json->features)) {
            return new AddressCollection([]);
        }

        $results = [];
        foreach ($json->features as $feature) {
            $coordinates = $feature->geometry->coordinates;

            switch ($feature->properties->type) {
                case self::TYPE_HOUSENUMBER:
                    $streetName = !empty($feature->properties->street) ? $feature->properties->street : null;
                    $number = !empty($feature->properties->housenumber) ? $feature->properties->housenumber : null;
                    break;
                case self::TYPE_STREET:
                    $streetName = !empty($feature->properties->name) ? $feature->properties->name : null;
                    $number = null;
                    break;
                default:
                    $streetName = null;
                    $number = null;
            }
            $locality = !empty($feature->properties->city) ? $feature->properties->city : null;
            $postalCode = !empty($feature->properties->postcode) ? $feature->properties->postcode : null;

            $results[] = Address::createFromArray([
                'providedBy'   => $this->getName(),
                'latitude'     => $coordinates[1],
                'longitude'    => $coordinates[0],
                'streetNumber' => $number,
                'streetName'   => $streetName,
                'locality'     => $locality,
                'postalCode'   => $postalCode,
                'adminLevels'  => $this->getAdminLevels($feature->properties),
            ]);
        }

        return new AddressCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseQuery(ReverseQuery $query): Collection
    {
        $coordinates = $query->getCoordinates();

        $url = sprintf($this->getReverseEndpointUrl(), $coordinates->getLatitude(), $coordinates->getLongitude());
        $json = $this->executeQuery($url);

        // no result
        if (empty($json->features)) {
            return new AddressCollection([]);
        }

        $results = [];
        foreach ($json->features as $feature) {
            $coordinates = $feature->geometry->coordinates;
            $streetName = !empty($feature->properties->street) ? $feature->properties->street : null;
            $number = !empty($feature->properties->housenumber) ? $feature->properties->housenumber : null;
            $municipality = !empty($feature->properties->city) ? $feature->properties->city : null;
            $postalCode = !empty($feature->properties->postcode) ? $feature->properties->postcode : null;

            $results[] = Address::createFromArray([
                'providedBy'   => $this->getName(),
                'latitude'     => $coordinates[1],
                'longitude'    => $coordinates[0],
                'streetNumber' => $number,
                'streetName'   => $streetName,
                'locality'     => $municipality,
                'postalCode'   => $postalCode,
                'adminLevels'  => $this->getAdminLevels($feature->properties),
            ]);
        }

        return new AddressCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'addok';
    }

    /**
     * @param string $url
     *
     * @return \stdClass
     */
    private function executeQuery(string $url): \stdClass
    {
        $content = $this->getUrlContents($url);
        $json = json_decode($content);
        // API error
        if (!isset($json)) {
            throw InvalidServerResponse::create($url);
        }

        return $json;
    }

    /**
     * @param \stdClass $properties
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAdminLevels(\stdClass $properties): array
    {
        $adminLevels = [];

        $context = !empty($properties->context) ? $properties->context : null;
        if ($context) {
            $contextParts = explode(',', $context);
            $departmentCode = trim($contextParts[0] ?? '');
            $departementLabel = trim($contextParts[1] ?? '');
            $regionLabel = trim($contextParts[2] ?? '');

            $adminLevels[] = ['level' => 2, 'name' => $regionLabel];
            $adminLevels[] = ['level' => 3, 'name' => $departementLabel, 'code' => $departmentCode];
        }

        $cityCode = !empty($properties->citycode) ? $properties->citycode : null;
        $municipality = !empty($properties->city) ? $properties->city : null;
        if ($cityCode && $municipality) {
            $adminLevels[] = ['level' => 4, 'name' => $municipality, 'code' => $cityCode];
        }

        $district = !empty($properties->district) ? $properties->district : null;
        $adminLevels[] = ['level' => 5, 'name' => $district];

        return $adminLevels;
    }
}
