<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Service;

use Cwx1227\Esxi\Value\DataObject;

final class NetworkService extends AbstractService
{
    public function listVirtualSwitches(mixed $host = null): array
    {
        $config = $this->client->host()->networkConfig($host);

        return $this->client->vmwareArray($config['config.network.vswitch'] ?? [], 'HostVirtualSwitch');
    }

    public function listPortGroups(mixed $host = null): array
    {
        $config = $this->client->host()->networkConfig($host);

        return $this->client->vmwareArray($config['config.network.portgroup'] ?? [], 'HostPortGroup');
    }

    public function listPhysicalNics(mixed $host = null): array
    {
        $config = $this->client->host()->networkConfig($host);

        return $this->client->vmwareArray($config['config.network.pnic'] ?? [], 'PhysicalNic');
    }

    public function listVmKernelNics(mixed $host = null): array
    {
        $config = $this->client->host()->networkConfig($host);

        return $this->client->vmwareArray($config['config.network.vnic'] ?? [], 'HostVirtualNic');
    }

    public function createVirtualSwitch(array $params, mixed $host = null): array
    {
        $this->assertAllowedParams($params, ['name', 'num_ports', 'mtu', 'pnics']);

        foreach (['name'] as $required) {
            if (empty($params[$required])) {
                throw new \InvalidArgumentException("Missing virtual switch parameter: {$required}");
            }
        }

        $spec = [];
        $spec['numPorts'] = $this->positiveInt($params['num_ports'] ?? 128, 'num_ports');
        if (isset($params['mtu'])) {
            $spec['mtu'] = $this->positiveInt($params['mtu'], 'mtu');
        }
        if (!empty($params['pnics'])) {
            $spec['bridge'] = DataObject::typed('HostVirtualSwitchBondBridge', [
                'nicDevice' => array_values((array) $params['pnics']),
            ]);
        }

        $this->client->addVirtualSwitch->execute(
            $this->client->hostNetworkSystem($host),
            $this->requiredString($params, 'name'),
            $spec === [] ? null : DataObject::typed('HostVirtualSwitchSpec', $spec)
        );

        return $this->ok(['name' => (string) $params['name']]);
    }

    public function removeVirtualSwitch(string $name, mixed $host = null): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Invalid parameter: name must be a non-empty string.');
        }

        $this->client->removeVirtualSwitch->execute($this->client->hostNetworkSystem($host), $name);

        return $this->ok(['name' => $name]);
    }

    public function createPortGroup(array $params, mixed $host = null): array
    {
        $this->assertPortGroupParams($params);

        foreach (['name', 'vswitch'] as $required) {
            if (empty($params[$required])) {
                throw new \InvalidArgumentException("Missing port group parameter: {$required}");
            }
        }

        $spec = $this->buildPortGroupSpec($params);
        $this->client->addPortGroup->execute(
            $this->client->hostNetworkSystem($host),
            $spec
        );

        return $this->ok([
            'name' => (string) $params['name'],
            'vswitch' => (string) $params['vswitch'],
            'vlan_id' => (int) ($params['vlan_id'] ?? 0),
        ]);
    }

    public function updatePortGroup(array $params, mixed $host = null): array
    {
        $this->assertPortGroupParams($params);

        foreach (['name', 'vswitch'] as $required) {
            if (empty($params[$required])) {
                throw new \InvalidArgumentException("Missing port group parameter: {$required}");
            }
        }

        $name = $this->requiredString($params, 'name');
        $spec = $this->buildPortGroupSpec($params);
        $this->client->updatePortGroup->execute(
            $this->client->hostNetworkSystem($host),
            $name,
            $spec
        );

        return $this->ok([
            'name' => (string) $params['name'],
            'vswitch' => (string) $params['vswitch'],
            'vlan_id' => (int) ($params['vlan_id'] ?? 0),
        ]);
    }

    public function removePortGroup(string $name, mixed $host = null): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Invalid parameter: name must be a non-empty string.');
        }

        $this->client->removePortGroup->execute($this->client->hostNetworkSystem($host), $name);

        return $this->ok(['name' => $name]);
    }

    private function buildPortGroupSpec(array $params): DataObject
    {
        $name = $this->requiredString($params, 'name');
        $vswitch = $this->requiredString($params, 'vswitch');
        $vlanId = $this->vlanId($params['vlan_id'] ?? 0);
        if ($vlanId < 0 || $vlanId > 4095) {
            throw new \InvalidArgumentException('Invalid parameter: vlan_id must be between 0 and 4095.');
        }

        $policy = [];
        $security = $params['security'] ?? [];
        if ($security !== []) {
            $securityPolicy = [];
            foreach ([
                'allow_promiscuous' => 'allowPromiscuous',
                'mac_changes' => 'macChanges',
                'forged_transmits' => 'forgedTransmits',
            ] as $input => $apiName) {
                if (array_key_exists($input, $security)) {
                    $securityPolicy[$apiName] = (bool) $security[$input];
                }
            }
            if ($securityPolicy !== []) {
                $policy['security'] = DataObject::typed('HostNetworkSecurityPolicy', $securityPolicy);
            }
        }

        $shaping = [];
        if (isset($params['bandwidth_mbps']) || isset($params['bandwidth_limit_mbps']) || isset($params['bandwidth_limit_bps'])) {
            $bandwidthLimit = isset($params['bandwidth_limit_bps'])
                ? $this->positiveInt($params['bandwidth_limit_bps'], 'bandwidth_limit_bps')
                : $this->positiveInt($params['bandwidth_mbps'] ?? $params['bandwidth_limit_mbps'], 'bandwidth_mbps') * 1000 * 1000;
            $shaping['enabled'] = true;
            $shaping['average_bandwidth'] = $bandwidthLimit;
            $shaping['peak_bandwidth'] = $bandwidthLimit;
            $shaping['burst_size'] = 1024 * 1024;
        }

        if ($shaping !== []) {
            $shapingPolicy = [];
            if (array_key_exists('enabled', $shaping)) {
                $shapingPolicy['enabled'] = (bool) $shaping['enabled'];
            }

            foreach ([
                'average_bandwidth' => 'averageBandwidth',
                'peak_bandwidth' => 'peakBandwidth',
                'burst_size' => 'burstSize',
            ] as $input => $apiName) {
                if (array_key_exists($input, $shaping)) {
                    $shapingPolicy[$apiName] = $this->positiveInt($shaping[$input], 'shaping.' . $input);
                }
            }

            if ($shapingPolicy !== []) {
                $policy['shapingPolicy'] = DataObject::typed('HostNetworkTrafficShapingPolicy', $shapingPolicy);
            }
        }

        return DataObject::typed('HostPortGroupSpec', [
            'name' => $name,
            'vlanId' => $vlanId,
            'vswitchName' => $vswitch,
            'policy' => DataObject::typed('HostNetworkPolicy', $policy),
        ]);
    }

    private function assertPortGroupParams(array $params): void
    {
        $this->assertAllowedParams($params, [
            'name',
            'vswitch',
            'vlan_id',
            'security',
            'bandwidth_mbps',
            'bandwidth_limit_mbps',
            'bandwidth_limit_bps',
        ]);

        if (isset($params['security'])) {
            if (!is_array($params['security'])) {
                throw new \InvalidArgumentException('Invalid parameter: security must be an array.');
            }
            $this->assertAllowedParams($params['security'], [
                'allow_promiscuous',
                'mac_changes',
                'forged_transmits',
            ], 'security');
        }

        foreach (['bandwidth_mbps', 'bandwidth_limit_mbps', 'bandwidth_limit_bps'] as $key) {
            if (isset($params[$key])) {
                $this->positiveInt($params[$key], $key);
            }
        }
    }

    private function requiredString(array $params, string $key): string
    {
        if (!isset($params[$key]) || !is_scalar($params[$key]) || trim((string) $params[$key]) === '') {
            throw new \InvalidArgumentException("Invalid parameter: {$key} must be a non-empty string.");
        }

        return trim((string) $params[$key]);
    }

    private function positiveInt(mixed $value, string $key): int
    {
        $int = null;
        if (is_int($value)) {
            $int = $value;
        } elseif (is_string($value) && preg_match('/^[1-9]\d*$/', trim($value)) === 1) {
            $int = (int) trim($value);
        }

        if ($int === null || $int <= 0) {
            throw new \InvalidArgumentException("Invalid parameter: {$key} must be a positive integer.");
        }

        return $int;
    }

    private function vlanId(mixed $value): int
    {
        $int = null;
        if (is_int($value)) {
            $int = $value;
        } elseif (is_string($value) && preg_match('/^(0|[1-9]\d*)$/', trim($value)) === 1) {
            $int = (int) trim($value);
        }

        if ($int === null || $int < 0 || $int > 4095) {
            throw new \InvalidArgumentException('Invalid parameter: vlan_id must be an integer between 0 and 4095.');
        }

        return $int;
    }
}
