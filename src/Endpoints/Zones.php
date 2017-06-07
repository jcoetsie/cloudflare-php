<?php
/**
 * Created by PhpStorm.
 * User: junade
 * Date: 06/06/2017
 * Time: 15:45
 */

namespace Cloudflare\API\Endpoints;

use Cloudflare\API\Adapter\Adapter;
use Symfony\Component\Config\Definition\Exception\Exception;

class Zones implements API
{
    private $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function addZone(string $name, bool $jumpstart = false, string $organizationID = ''): \stdClass
    {
        $options = [
            'name' => $name,
            'jumpstart' => $jumpstart
        ];

        if (!empty($organizationID)) {
            $organization = new \stdClass();
            $organization->id = $organizationID;
            $options["organization"] = $organization;
        }

        $user = $this->adapter->post('zones', [], $options);
        $body = json_decode($user->getBody());
        return $body->result;
    }

    public function activationCheck(string $zoneID): bool
    {
        $user = $this->adapter->put('zones/' . $zoneID . '/activation_check', [], []);
        $body = json_decode($user->getBody());

        if (isset($body->result->id)) {
            return true;
        }

        return false;
    }

    public function listZones(
        string $name = "",
        string $status = "",
        int $page = 1,
        int $perPage = 20,
        string $order = "",
        string $direction = "",
        string $match = "all"
    ): \stdClass {
        $options = [
            'page' => $page,
            'per_page' => $perPage,
            'match' => $match
        ];

        if (!empty($name)) {
            $options['name'] = $name;
        }

        if (!empty($status)) {
            $options['status'] = $status;
        }

        if (!empty($order)) {
            $options['order'] = $order;
        }

        if (!empty($direction)) {
            $options['direction'] = $direction;
        }

        $user = $this->adapter->get('zones', [], $options);
        $body = json_decode($user->getBody());

        $result = new \stdClass();
        $result->result = $body->result;
        $result->result_info = $body->result_info;

        return $result;
    }

    public function getZoneID(string $name = ""): string
    {
        $zones = $this->listZones($name);

        if (sizeof($zones) < 1) {
            throw new EndpointException("Could not find zones with specified name.");
        }

        return $zones->result[0]->id;
    }

    /**
     * Purge Everything
     * @param string $zoneID
     * @return bool
     */
    public function purgeAll(string $zoneID): bool
    {
        $user = $this->adapter->delete('zones/' . $zoneID . '/purge_cache', [], ["purge_everything" => true]);

        $body = json_decode($user->getBody());

        if (isset($body->result->id)) {
            return true;
        }

        return false;
    }

    public function purge(string $zoneID, array $files = [], array $tags = []): bool
    {
        if (empty($files) && empty($tags)) {
            throw new Exception("No files or tags to purge.");
        }

        $options = [
            'files' => $files,
            'tags' => $tags
        ];

        $user = $this->adapter->delete('zones/' . $zoneID . '/purge_cache', [], $options);

        $body = json_decode($user->getBody());

        if (isset($body->result->id)) {
            return true;
        }

        return false;
    }
}