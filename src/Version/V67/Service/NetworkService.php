<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Service;

use WebmanVps\Esxi\Value\DataObject;

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
        foreach (['name'] as $required) {
            if (empty($params[$required])) {
                throw new \InvalidArgumentException("Missing virtual switch parameter: {$required}");
            }
        }

        $spec = [];
        $spec['numPorts'] = (int) ($params['num_ports'] ?? 128);
        if (isset($params['mtu'])) {
            $spec['mtu'] = (int) $params['mtu'];
        }
        if (!empty($params['pnics'])) {
            $spec['bridge'] = DataObject::typed('HostVirtualSwitchBondBridge', [
                'nicDevice' => array_values((array) $params['pnics']),
            ]);
        }

        $this->client->addVirtualSwitch->execute(
            $this->client->hostNetworkSystem($host),
            (string) $params['name'],
            $spec === [] ? null : DataObject::typed('HostVirtualSwitchSpec', $spec)
        );

        return $this->ok(['name' => (string) $params['name']]);
    }

    public function removeVirtualSwitch(string $name, mixed $host = null): array
    {
        $this->client->removeVirtualSwitch->execute($this->client->hostNetworkSystem($host), $name);

        return $this->ok(['name' => $name]);
    }

    public function createPortGroup(array $params, mixed $host = null): array
    {
        foreach (['name', 'vswitch'] as $required) {
            if (empty($params[$required])) {
                throw new \InvalidArgumentException("Missing port group parameter: {$required}");
            }
        }

        $this->client->addPortGroup->execute(
            $this->client->hostNetworkSystem($host),
            $this->buildPortGroupSpec($params)
        );

        return $this->ok([
            'name' => (string) $params['name'],
            'vswitch' => (string) $params['vswitch'],
            'vlan_id' => (int) ($params['vlan_id'] ?? 0),
        ]);
    }

    public function updatePortGroup(array $params, mixed $host = null): array
    {
        foreach (['name', 'vswitch'] as $required) {
            if (empty($params[$required])) {
                throw new \InvalidArgumentException("Missing port group parameter: {$required}");
            }
        }

        $this->client->updatePortGroup->execute(
            $this->client->hostNetworkSystem($host),
            (string) $params['name'],
            $this->buildPortGroupSpec($params)
        );

        return $this->ok([
            'name' => (string) $params['name'],
            'vswitch' => (string) $params['vswitch'],
            'vlan_id' => (int) ($params['vlan_id'] ?? 0),
        ]);
    }

    public function removePortGroup(string $name, mixed $host = null): array
    {
        $this->client->removePortGroup->execute($this->client->hostNetworkSystem($host), $name);

        return $this->ok(['name' => $name]);
    }

    private function buildPortGroupSpec(array $params): DataObject
    {
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

        $shaping = $params['shaping'] ?? $params['bandwidth'] ?? [];
        if (isset($params['bandwidth_limit_bps'])) {
            $shaping['enabled'] = true;
            $shaping['average_bandwidth'] = (int) $params['bandwidth_limit_bps'];
            $shaping['peak_bandwidth'] = (int) $params['bandwidth_limit_bps'];
            $shaping['burst_size'] = (int) ($params['burst_size'] ?? 1024 * 1024);
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
                    $shapingPolicy[$apiName] = (int) $shaping[$input];
                }
            }

            if ($shapingPolicy !== []) {
                $policy['shapingPolicy'] = DataObject::typed('HostNetworkTrafficShapingPolicy', $shapingPolicy);
            }
        }

        return DataObject::typed('HostPortGroupSpec', [
            'name' => (string) $params['name'],
            'vlanId' => (int) ($params['vlan_id'] ?? 0),
            'vswitchName' => (string) $params['vswitch'],
            'policy' => DataObject::typed('HostNetworkPolicy', $policy),
        ]);
    }
}
