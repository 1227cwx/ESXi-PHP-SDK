<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Service;

use WebmanVps\Esxi\Exception\EsxiException;
use WebmanVps\Esxi\Value\DataObject;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;

final class VpsService extends AbstractService
{
    public function list(array $properties = []): array
    {
        return $this->ok($this->rows($properties));
    }

    public function rows(array $properties = []): array
    {
        $properties = $properties ?: [
            'name',
            'config.uuid',
            'config.instanceUuid',
            'summary.config.vmPathName',
            'summary.config.guestFullName',
            'summary.config.numCpu',
            'summary.config.memorySizeMB',
            'runtime.powerState',
            'summary.guest.ipAddress',
            'summary.guest.toolsStatus',
            'summary.quickStats.overallCpuUsage',
            'summary.quickStats.hostMemoryUsage',
            'summary.quickStats.guestMemoryUsage',
            'summary.quickStats.uptimeSeconds',
            'summary.storage.committed',
            'summary.storage.uncommitted',
        ];

        return $this->client->retrieveByContainerView('VirtualMachine', $properties);
    }

    public function info(mixed $vm, array $properties = []): array
    {
        return $this->ok($this->rawInfo($vm, $properties));
    }

    public function rawInfo(mixed $vm, array $properties = []): array
    {
        $properties = $properties ?: [
            'name',
            'config.uuid',
            'config.instanceUuid',
            'config.files.vmPathName',
            'config.guestFullName',
            'config.hardware.numCPU',
            'config.hardware.memoryMB',
            'config.hardware.device',
            'runtime.powerState',
            'guest.ipAddress',
            'summary.quickStats',
            'summary.storage',
        ];

        return $this->client->retrieveObjectProperties(
            $this->client->resolveVirtualMachine($vm),
            'VirtualMachine',
            $properties
        );
    }

    public function create(array $params, bool $wait = true): array
    {
        foreach (['name', 'datastore', 'network', 'disk_gb', 'memory_mb', 'num_cpus'] as $required) {
            if (!array_key_exists($required, $params)) {
                throw new \InvalidArgumentException("Missing VPS create parameter: {$required}");
            }
        }

        $name = (string) $params['name'];
        $datastore = (string) $params['datastore'];
        $network = (string) $params['network'];
        $vmPath = $params['vm_path'] ?? '[' . $datastore . '] ' . $name . '/' . $name . '.vmx';
        $diskPath = $params['disk_path'] ?? '[' . $datastore . '] ' . $name . '/' . $name . '.vmdk';

        $config = DataObject::typed('VirtualMachineConfigSpec', [
            'name' => $name,
            'guestId' => $params['guest_id'] ?? 'otherGuest64',
            'files' => DataObject::typed('VirtualMachineFileInfo', [
                'vmPathName' => $vmPath,
            ]),
            'numCPUs' => (int) $params['num_cpus'],
            'memoryMB' => (int) $params['memory_mb'],
            'deviceChange' => $this->buildCreateDeviceChanges(
                (int) $params['disk_gb'],
                $diskPath,
                $network,
                $params['adapter_type'] ?? 'vmxnet3',
                (bool) ($params['thin_provision'] ?? true)
            ),
        ]);

        $task = $this->client->createVMTask->execute(
            $this->morOption($params['folder'] ?? null, 'Folder', 'ha-folder-vm'),
            $config,
            $this->morOption($params['resource_pool'] ?? null, 'ResourcePool', 'ha-root-pool'),
            $this->morOption($params['host'] ?? null, 'HostSystem', 'ha-host')
        );

        return $this->taskResult($task, $wait, [
            'name' => $name,
            'datastore' => $datastore,
            'network' => $network,
        ]);
    }

    public function register(string $vmxPath, ?string $name = null, bool $wait = true, array $placement = []): array
    {
        $task = $this->client->registerVMTask->execute(
            $this->morOption($placement['folder'] ?? null, 'Folder', 'ha-folder-vm'),
            $vmxPath,
            $name,
            false,
            $this->morOption($placement['resource_pool'] ?? null, 'ResourcePool', 'ha-root-pool'),
            $this->morOption($placement['host'] ?? null, 'HostSystem', 'ha-host')
        );

        return $this->taskResult($task, $wait, ['path' => $vmxPath, 'name' => $name]);
    }

    public function resize(mixed $vm, array $params, bool $wait = true): array
    {
        $spec = [];
        if (isset($params['num_cpus'])) {
            $spec['numCPUs'] = (int) $params['num_cpus'];
        }
        if (isset($params['cpu'])) {
            $spec['numCPUs'] = (int) $params['cpu'];
        }
        if (isset($params['memory_mb'])) {
            $spec['memoryMB'] = (int) $params['memory_mb'];
        }
        if ($spec === []) {
            throw new \InvalidArgumentException('At least one of cpu/num_cpus or memory_mb is required.');
        }

        return $this->reconfigure($vm, $spec, $wait);
    }

    public function reconfigure(mixed $vm, array|DataObject $configSpec, bool $wait = true): array
    {
        $task = $this->client->reconfigVMTask->execute(
            $this->client->resolveVirtualMachine($vm),
            $configSpec instanceof DataObject ? $configSpec : DataObject::typed('VirtualMachineConfigSpec', $configSpec)
        );

        return $this->taskResult($task, $wait);
    }

    public function powerOn(mixed $vm, bool $wait = true): array
    {
        return $this->taskResult($this->client->powerOnVMTask->execute($this->client->resolveVirtualMachine($vm)), $wait);
    }

    public function powerOff(mixed $vm, bool $wait = true): array
    {
        return $this->taskResult($this->client->powerOffVMTask->execute($this->client->resolveVirtualMachine($vm)), $wait);
    }

    public function reset(mixed $vm, bool $wait = true): array
    {
        return $this->taskResult($this->client->resetVMTask->execute($this->client->resolveVirtualMachine($vm)), $wait);
    }

    public function suspend(mixed $vm, bool $wait = true): array
    {
        return $this->taskResult($this->client->suspendVMTask->execute($this->client->resolveVirtualMachine($vm)), $wait);
    }

    public function shutdownGuest(mixed $vm): array
    {
        $this->client->shutdownGuest->execute($this->client->resolveVirtualMachine($vm));

        return $this->ok();
    }

    public function rebootGuest(mixed $vm): array
    {
        $this->client->rebootGuest->execute($this->client->resolveVirtualMachine($vm));

        return $this->ok();
    }

    public function nics(mixed $vm): array
    {
        $info = $this->rawInfo($vm, ['config.hardware.device']);
        $nics = [];

        foreach ($this->client->vmwareArray($info['config.hardware.device'] ?? [], 'VirtualDevice') as $device) {
            if (!is_array($device)) {
                continue;
            }
            $type = $device['_xsi_type'] ?? '';
            if (in_array($type, ['VirtualE1000', 'VirtualE1000e', 'VirtualVmxnet3'], true)) {
                $nics[] = $device;
            }
        }

        return $this->ok($nics);
    }

    public function setNetwork(mixed $vm, string $networkName, array $params = [], bool $wait = true): array
    {
        $vmMor = $this->client->resolveVirtualMachine($vm);
        $info = $this->client->retrieveObjectProperties($vmMor, 'VirtualMachine', ['config.hardware.device']);
        $nic = $this->firstVirtualNic($info['config.hardware.device'] ?? null);
        $adapterType = $params['adapter_type'] ?? ($nic['_xsi_type'] ?? 'VirtualVmxnet3');

        $deviceParams = [
            'key' => $nic['key'] ?? -200,
            'backing' => DataObject::typed('VirtualEthernetCardNetworkBackingInfo', [
                'deviceName' => $networkName,
            ]),
            'connectable' => DataObject::typed('VirtualDeviceConnectInfo', [
                'startConnected' => $params['start_connected'] ?? true,
                'allowGuestControl' => $params['allow_guest_control'] ?? true,
                'connected' => $params['connected'] ?? true,
            ]),
            'addressType' => $params['address_type'] ?? 'generated',
        ];

        $task = $this->client->reconfigVMTask->execute($vmMor, DataObject::typed('VirtualMachineConfigSpec', [
            'deviceChange' => DataObject::typed('VirtualDeviceConfigSpec', [
                'operation' => $nic === null ? 'add' : 'edit',
                'device' => DataObject::typed($this->adapterType((string) $adapterType), $deviceParams),
            ]),
        ]));

        return $this->taskResult($task, $wait, ['network' => $networkName]);
    }

    private function taskResult(Mor $task, bool $wait, array $data = []): array
    {
        if (!$wait) {
            return [
                'success' => true,
                'task' => $task->jsonSerialize(),
                'data' => $data,
            ];
        }

        $result = $this->client->waitForTask($task);
        $result['data'] = array_replace($data, $result['data'] ?? []);

        return $result;
    }

    private function morOption(mixed $value, string $type, string $default): Mor
    {
        if ($value === null) {
            return new Mor($type, $default);
        }

        return Mor::from($value, $type);
    }

    private function buildCreateDeviceChanges(
        int $diskGb,
        string $diskPath,
        string $network,
        string $adapterType,
        bool $thinProvision
    ): array {
        $scsiKey = -100;
        $diskKey = -101;
        $nicKey = -102;

        return [
            DataObject::typed('VirtualDeviceConfigSpec', [
                'operation' => 'add',
                'device' => DataObject::typed('VirtualLsiLogicController', [
                    'key' => $scsiKey,
                    'busNumber' => 0,
                    'sharedBus' => 'noSharing',
                ]),
            ]),
            DataObject::typed('VirtualDeviceConfigSpec', [
                'operation' => 'add',
                'fileOperation' => 'create',
                'device' => DataObject::typed('VirtualDisk', [
                    'key' => $diskKey,
                    'backing' => DataObject::typed('VirtualDiskFlatVer2BackingInfo', [
                        'fileName' => $diskPath,
                        'diskMode' => 'persistent',
                        'thinProvisioned' => $thinProvision,
                    ]),
                    'controllerKey' => $scsiKey,
                    'unitNumber' => 0,
                    'capacityInKB' => $diskGb * 1024 * 1024,
                ]),
            ]),
            DataObject::typed('VirtualDeviceConfigSpec', [
                'operation' => 'add',
                'device' => DataObject::typed($this->adapterType($adapterType), [
                    'key' => $nicKey,
                    'backing' => DataObject::typed('VirtualEthernetCardNetworkBackingInfo', [
                        'deviceName' => $network,
                    ]),
                    'connectable' => DataObject::typed('VirtualDeviceConnectInfo', [
                        'startConnected' => true,
                        'allowGuestControl' => true,
                        'connected' => true,
                    ]),
                    'addressType' => 'generated',
                ]),
            ]),
        ];
    }

    private function adapterType(string $type): string
    {
        return match (strtolower($type)) {
            'e1000' => 'VirtualE1000',
            'e1000e' => 'VirtualE1000e',
            'vmxnet3' => 'VirtualVmxnet3',
            default => $type,
        };
    }

    private function firstVirtualNic(mixed $devices): ?array
    {
        foreach ($this->client->vmwareArray($devices, 'VirtualDevice') as $device) {
            if (!is_array($device)) {
                continue;
            }

            $type = $device['_xsi_type'] ?? '';
            if (in_array($type, ['VirtualE1000', 'VirtualE1000e', 'VirtualVmxnet3'], true)) {
                return $device;
            }
        }

        return null;
    }
}
