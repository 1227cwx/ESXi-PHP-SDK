<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Service;

use Cwx1227\Esxi\Exception\EsxiException;
use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;

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

    public function status(mixed $vm): array
    {
        return $this->ok($this->rawInfo($vm, [
            'name',
            'runtime.powerState',
            'runtime.connectionState',
            'runtime.bootTime',
            'guest.guestState',
            'guest.toolsStatus',
            'guest.ipAddress',
            'overallStatus',
        ]));
    }

    public function config(mixed $vm): array
    {
        return $this->ok($this->rawInfo($vm, [
            'name',
            'config.uuid',
            'config.instanceUuid',
            'config.files',
            'config.guestFullName',
            'config.guestId',
            'config.version',
            'config.hardware',
            'config.annotation',
        ]));
    }

    public function usage(mixed $vm): array
    {
        $row = $this->rawInfo($vm, [
            'name',
            'summary.config.numCpu',
            'summary.config.memorySizeMB',
            'summary.quickStats.overallCpuUsage',
            'summary.quickStats.overallCpuDemand',
            'summary.quickStats.hostMemoryUsage',
            'summary.quickStats.guestMemoryUsage',
            'summary.quickStats.uptimeSeconds',
            'summary.storage.committed',
            'summary.storage.uncommitted',
            'runtime.powerState',
        ]);

        return $this->ok([
            'name' => $row['name'] ?? null,
            'power_state' => $row['runtime.powerState'] ?? null,
            'cpu' => [
                'num_cpu' => $row['summary.config.numCpu'] ?? null,
                'used_mhz' => $row['summary.quickStats.overallCpuUsage'] ?? null,
                'demand_mhz' => $row['summary.quickStats.overallCpuDemand'] ?? null,
            ],
            'memory' => [
                'configured_mb' => $row['summary.config.memorySizeMB'] ?? null,
                'host_used_mb' => $row['summary.quickStats.hostMemoryUsage'] ?? null,
                'guest_used_mb' => $row['summary.quickStats.guestMemoryUsage'] ?? null,
            ],
            'disk' => [
                'committed_bytes' => $row['summary.storage.committed'] ?? null,
                'uncommitted_bytes' => $row['summary.storage.uncommitted'] ?? null,
            ],
            'uptime_seconds' => $row['summary.quickStats.uptimeSeconds'] ?? null,
            'raw' => $row,
        ]);
    }

    public function metrics(mixed $vm): array
    {
        return $this->usage($vm);
    }

    public function monitor(mixed $vm): array
    {
        return $this->usage($vm);
    }

    public function create(array $params, bool $wait = true): array
    {
        $this->assertAllowedParams($params, [
            'name',
            'datastore',
            'network',
            'memory_mb',
            'num_cpus',
            'disk_gb',
            'use_existing_disk',
            'existing_disk',
            'disk_path',
            'vm_path',
            'guest_id',
            'hardware_version',
            'vmx_version',
            'version',
            'scsi_controller',
            'scsi_controller_type',
            'adapter_type',
            'thin_provision',
        ]);

        foreach (['name', 'datastore', 'network', 'memory_mb', 'num_cpus'] as $required) {
            if (!array_key_exists($required, $params)) {
                throw new \InvalidArgumentException("Missing VPS create parameter: {$required}");
            }
        }

        $name = $this->requiredString($params, 'name');
        $datastore = $this->requiredString($params, 'datastore');
        $network = $this->requiredString($params, 'network');
        $numCpus = $this->positiveInt($params['num_cpus'], 'num_cpus');
        $memoryMb = $this->positiveInt($params['memory_mb'], 'memory_mb');
        $useExistingDisk = $this->useExistingDisk($params);
        $diskGb = isset($params['disk_gb']) ? $this->positiveInt($params['disk_gb'], 'disk_gb') : null;
        if ($diskGb === null) {
            throw new \InvalidArgumentException('Missing VPS create parameter: disk_gb');
        }
        $vmPath = isset($params['vm_path'])
            ? $this->datastorePath($this->requiredString($params, 'vm_path'), 'vm_path')
            : '[' . $datastore . '] ' . $name . '/' . $name . '.vmx';
        $diskPath = isset($params['disk_path'])
            ? $this->datastorePath($this->requiredString($params, 'disk_path'), 'disk_path')
            : '[' . $datastore . '] ' . $name . '/' . $name . '.vmdk';
        if ($useExistingDisk && !isset($params['disk_path'])) {
            throw new \InvalidArgumentException('Missing existing disk parameter: disk_path');
        }
        $capacityInKb = $diskGb * 1024 * 1024;

        $configSpec = [
            'name' => $name,
        ];
        if (isset($params['hardware_version']) || isset($params['vmx_version']) || isset($params['version'])) {
            $configSpec['version'] = $this->requiredString([
                'version' => $params['hardware_version'] ?? $params['vmx_version'] ?? $params['version'],
            ], 'version');
        }

        $configSpec += [
            'guestId' => $params['guest_id'] ?? 'otherGuest64',
            'files' => DataObject::typed('VirtualMachineFileInfo', [
                'vmPathName' => $vmPath,
            ]),
            'numCPUs' => $numCpus,
            'memoryMB' => $memoryMb,
            'deviceChange' => $this->buildCreateDeviceChanges(
                $capacityInKb,
                $diskPath,
                $network,
                $params['scsi_controller'] ?? $params['scsi_controller_type'] ?? 'lsilogic',
                $params['adapter_type'] ?? 'vmxnet3',
                (bool) ($params['thin_provision'] ?? true),
                !$useExistingDisk
            ),
        ];

        $config = DataObject::typed('VirtualMachineConfigSpec', $configSpec);

        $task = $this->client->createVMTask->execute(
            new Mor('Folder', 'ha-folder-vm'),
            $config,
            new Mor('ResourcePool', 'ha-root-pool'),
            new Mor('HostSystem', 'ha-host')
        );

        return $this->taskResult($task, $wait, [
            'name' => $name,
            'datastore' => $datastore,
            'network' => $network,
        ]);
    }

    public function resize(mixed $vm, array $params, bool $wait = true): array
    {
        return $this->modifyConfig($vm, $params, $wait);
    }

    public function modifyConfig(mixed $vm, array $params, bool $wait = true): array
    {
        $this->assertAllowedParams($params, [
            'num_cpus',
            'cpu',
            'memory_mb',
            'disk_gb',
            'disk_size_gb',
            'capacity_gb',
            'add_disk',
            'add_disks',
            'add_network',
            'add_networks',
        ]);

        $spec = [];
        if (isset($params['num_cpus'])) {
            $spec['numCPUs'] = $this->positiveInt($params['num_cpus'], 'num_cpus');
        }
        if (isset($params['cpu'])) {
            $spec['numCPUs'] = $this->positiveInt($params['cpu'], 'cpu');
        }
        if (isset($params['memory_mb'])) {
            $spec['memoryMB'] = $this->positiveInt($params['memory_mb'], 'memory_mb');
        }

        $vmMor = $this->client->resolveVirtualMachine($vm);
        $deviceChanges = [];

        if (
            isset($params['disk_gb'])
            || isset($params['disk_size_gb'])
            || isset($params['capacity_gb'])
        ) {
            $deviceChanges[] = $this->buildResizeDiskChange($vmMor, $params);
        }

        $reservedUnitNumbers = [];
        foreach ($this->normalizeDeviceItems($params, 'add_disk', 'add_disks') as $diskParams) {
            $deviceChanges[] = $this->buildAddDiskChange($vmMor, $diskParams, $reservedUnitNumbers);
        }

        $networkIndex = 0;
        foreach ($this->normalizeDeviceItems($params, 'add_network', 'add_networks') as $networkParams) {
            $networkName = (string) ($networkParams['network'] ?? $networkParams['port_group'] ?? $networkParams['name'] ?? '');
            if ($networkName === '') {
                throw new \InvalidArgumentException('Missing add network parameter: network');
            }

            $deviceChanges[] = $this->buildAddNetworkChange($networkName, $networkParams, -500 - $networkIndex);
            $networkIndex++;
        }

        if ($deviceChanges !== []) {
            $spec['deviceChange'] = $deviceChanges;
        }

        if ($spec === []) {
            throw new \InvalidArgumentException('At least one VM config parameter is required.');
        }

        return $this->executeReconfigure($vmMor, $spec, $wait);
    }

    public function resizeDisk(mixed $vm, int|array $params, bool $wait = true): array
    {
        $params = is_int($params) ? ['disk_gb' => $params] : $params;

        return $this->modifyConfig($vm, $params, $wait);
    }

    public function addDisk(mixed $vm, array $params, bool $wait = true): array
    {
        return $this->modifyConfig($vm, ['add_disk' => $params], $wait);
    }

    public function addNetwork(mixed $vm, string $networkName, array $params = [], bool $wait = true): array
    {
        $params['network'] = $networkName;

        return $this->modifyConfig($vm, ['add_network' => $params], $wait);
    }

    public function reconfigure(mixed $vm, array $params, bool $wait = true): array
    {
        return $this->modifyConfig($vm, $params, $wait);
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

    public function delete(mixed $vm, bool $wait = true): array
    {
        return $this->taskResult($this->client->destroyTask->execute($this->client->resolveVirtualMachine($vm)), $wait);
    }

    public function destroy(mixed $vm, bool $wait = true): array
    {
        return $this->delete($vm, $wait);
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
        $this->assertAllowedParams($params, [
            'adapter_type',
            'start_connected',
            'allow_guest_control',
            'connected',
        ]);

        $networkName = trim($networkName);
        if ($networkName === '') {
            throw new \InvalidArgumentException('Invalid parameter: networkName must be a non-empty string.');
        }

        $vmMor = $this->client->resolveVirtualMachine($vm);
        $info = $this->client->retrieveObjectProperties($vmMor, 'VirtualMachine', ['config.hardware.device']);
        $nic = $this->firstVirtualNic($info['config.hardware.device'] ?? null);
        $adapterType = $params['adapter_type'] ?? $this->adapterAliasFromDeviceType((string) ($nic['_xsi_type'] ?? 'VirtualVmxnet3'));

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
            'addressType' => 'generated',
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

    private function buildCreateDeviceChanges(
        ?int $capacityInKb,
        string $diskPath,
        string $network,
        string $scsiControllerType,
        string $adapterType,
        bool $thinProvision,
        bool $createDisk
    ): array {
        $scsiKey = -100;
        $diskKey = -101;
        $nicKey = -102;
        $disk = [
            'key' => $diskKey,
            'backing' => DataObject::typed('VirtualDiskFlatVer2BackingInfo', array_filter([
                'fileName' => $diskPath,
                'diskMode' => 'persistent',
                'thinProvisioned' => $createDisk ? $thinProvision : null,
            ], static fn (mixed $value): bool => $value !== null)),
            'controllerKey' => $scsiKey,
            'unitNumber' => 0,
        ];
        if ($capacityInKb !== null) {
            $disk['capacityInKB'] = $capacityInKb;
        }

        return [
            DataObject::typed('VirtualDeviceConfigSpec', [
                'operation' => 'add',
                'device' => DataObject::typed($this->scsiControllerType($scsiControllerType), [
                    'key' => $scsiKey,
                    'busNumber' => 0,
                    'sharedBus' => 'noSharing',
                ]),
            ]),
            DataObject::typed('VirtualDeviceConfigSpec', [
                'operation' => 'add',
                'fileOperation' => $createDisk ? 'create' : null,
                'device' => DataObject::typed('VirtualDisk', $disk),
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

    private function buildResizeDiskChange(Mor $vm, array $params): DataObject
    {
        $this->assertAllowedParams($params, [
            'disk_gb',
            'disk_size_gb',
            'capacity_gb',
        ]);

        $diskGb = $params['disk_gb'] ?? $params['disk_size_gb'] ?? $params['capacity_gb'] ?? null;
        if ($diskGb === null) {
            throw new \InvalidArgumentException('Missing disk resize parameter: disk_gb');
        }
        $capacityInKb = $this->positiveInt($diskGb, 'disk_gb') * 1024 * 1024;

        if ($capacityInKb <= 0) {
            throw new \InvalidArgumentException('Missing disk resize parameter: disk_gb');
        }

        $info = $this->client->retrieveObjectProperties($vm, 'VirtualMachine', [
            'config.hardware.device',
        ]);
        $disk = $this->selectVirtualDisk($info['config.hardware.device'] ?? null);
        $disk['capacityInKB'] = $capacityInKb;
        unset($disk['capacityInBytes']);

        return DataObject::typed('VirtualDeviceConfigSpec', [
            'operation' => 'edit',
            'device' => $this->dataObjectFromDecoded($disk, 'VirtualDisk'),
        ]);
    }

    private function buildAddDiskChange(Mor $vm, array $params, array &$reservedUnitNumbers = []): DataObject
    {
        $this->assertAllowedParams($params, [
            'disk_gb',
            'size_gb',
            'capacity_gb',
            'use_existing_disk',
            'existing_disk',
            'disk_path',
            'datastore',
            'folder',
            'thin_provision',
        ]);

        $useExistingDisk = $this->useExistingDisk($params);
        $diskGb = $params['disk_gb'] ?? $params['size_gb'] ?? $params['capacity_gb'] ?? null;
        $capacityInKb = $diskGb === null ? 0 : $this->positiveInt($diskGb, 'disk_gb') * 1024 * 1024;

        if (!$useExistingDisk && $capacityInKb <= 0) {
            throw new \InvalidArgumentException('Missing add disk parameter: disk_gb');
        }
        if ($useExistingDisk && empty($params['disk_path'])) {
            throw new \InvalidArgumentException('Missing existing disk parameter: disk_path');
        }
        if ($useExistingDisk && $capacityInKb <= 0) {
            throw new \InvalidArgumentException('Missing existing disk capacity parameter: disk_gb');
        }

        $info = $this->client->retrieveObjectProperties($vm, 'VirtualMachine', [
            'name',
            'config.files.vmPathName',
            'config.hardware.device',
        ]);

        $devices = $this->client->vmwareArray($info['config.hardware.device'] ?? [], 'VirtualDevice');
        $controller = $this->selectScsiController($devices);
        $controllerKey = (int) ($controller['key'] ?? 0);
        if ($controllerKey === 0) {
            throw new EsxiException('SCSI controller not found.');
        }

        $unitNumber = $this->nextDiskUnitNumber($devices, $controllerKey, $reservedUnitNumbers);
        $reservedUnitNumbers[] = $unitNumber;

        $diskPath = isset($params['disk_path'])
            ? $this->datastorePath((string) $params['disk_path'], 'disk_path')
            : $this->defaultDiskPath($info, $unitNumber, $params);

        $disk = [
            'key' => $this->nextNegativeKey($devices, -300 - count($reservedUnitNumbers)),
            'backing' => DataObject::typed('VirtualDiskFlatVer2BackingInfo', array_filter([
                'fileName' => $diskPath,
                'diskMode' => 'persistent',
                'thinProvisioned' => $useExistingDisk ? null : (bool) ($params['thin_provision'] ?? true),
            ], static fn (mixed $value): bool => $value !== null)),
            'controllerKey' => $controllerKey,
            'unitNumber' => $unitNumber,
        ];
        if ($capacityInKb > 0) {
            $disk['capacityInKB'] = $capacityInKb;
        }

        return DataObject::typed('VirtualDeviceConfigSpec', [
            'operation' => 'add',
            'fileOperation' => $useExistingDisk ? null : 'create',
            'device' => DataObject::typed('VirtualDisk', $disk),
        ]);
    }

    private function buildAddNetworkChange(string $networkName, array $params = [], int $key = -500): DataObject
    {
        $this->assertAllowedParams($params, [
            'network',
            'port_group',
            'name',
            'adapter_type',
            'start_connected',
            'allow_guest_control',
            'connected',
        ]);

        return DataObject::typed('VirtualDeviceConfigSpec', [
            'operation' => 'add',
            'device' => DataObject::typed($this->adapterType((string) ($params['adapter_type'] ?? 'vmxnet3')), [
                'key' => (int) ($params['key'] ?? $key),
                'backing' => DataObject::typed('VirtualEthernetCardNetworkBackingInfo', [
                    'deviceName' => $networkName,
                ]),
                'connectable' => DataObject::typed('VirtualDeviceConnectInfo', [
                    'startConnected' => $params['start_connected'] ?? true,
                    'allowGuestControl' => $params['allow_guest_control'] ?? true,
                    'connected' => $params['connected'] ?? true,
                ]),
                'addressType' => 'generated',
            ]),
        ]);
    }

    private function scsiControllerType(string $type): string
    {
        return match (strtolower($type)) {
            'lsi', 'lsilogic', 'lsilogicparallel', 'parallel' => 'VirtualLsiLogicController',
            'lsisas', 'lsilogicsas', 'sas' => 'VirtualLsiLogicSASController',
            'buslogic' => 'VirtualBusLogicController',
            'pvscsi', 'paravirtual', 'paravirtualscsi' => 'ParaVirtualSCSIController',
            default => throw new \InvalidArgumentException('Invalid parameter: scsi_controller is not supported.'),
        };
    }

    private function adapterType(string $type): string
    {
        return match (strtolower($type)) {
            'e1000' => 'VirtualE1000',
            'e1000e' => 'VirtualE1000e',
            'vmxnet3' => 'VirtualVmxnet3',
            default => throw new \InvalidArgumentException('Invalid parameter: adapter_type is not supported.'),
        };
    }

    private function adapterAliasFromDeviceType(string $type): string
    {
        return match ($type) {
            'VirtualE1000' => 'e1000',
            'VirtualE1000e' => 'e1000e',
            default => 'vmxnet3',
        };
    }

    private function normalizeDeviceItems(array $params, string $singularKey, string $pluralKey): array
    {
        if (array_key_exists($pluralKey, $params)) {
            $items = $params[$pluralKey];
        } elseif (array_key_exists($singularKey, $params)) {
            $items = $params[$singularKey];
        } else {
            return [];
        }

        if (is_string($items)) {
            return [['network' => $items]];
        }

        if (!is_array($items)) {
            return [];
        }

        if ($items === []) {
            return [];
        }

        if (!array_is_list($items)) {
            return [$items];
        }

        return array_map(
            fn (mixed $item): array => is_string($item) ? ['network' => $item] : (array) $item,
            $items
        );
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

    private function datastorePath(string $path, string $key): string
    {
        $path = trim($path);
        if ($path === '' || preg_match('/^\[[^\]]+]($|\s+.+)/', $path) !== 1) {
            throw new \InvalidArgumentException("Invalid parameter: {$key} must be a datastore path like \"[datastore1] folder/file\".");
        }

        return $path;
    }

    private function useExistingDisk(array $params): bool
    {
        if (array_key_exists('use_existing_disk', $params)) {
            return (bool) $params['use_existing_disk'];
        }

        if (array_key_exists('existing_disk', $params)) {
            return (bool) $params['existing_disk'];
        }

        return false;
    }

    private function selectVirtualDisk(mixed $devices): array
    {
        $disks = [];
        foreach ($this->client->vmwareArray($devices, 'VirtualDevice') as $device) {
            if (is_array($device) && ($device['_xsi_type'] ?? '') === 'VirtualDisk') {
                $disks[] = $device;
            }
        }

        if (isset($disks[0])) {
            return $disks[0];
        }

        throw new EsxiException('Virtual disk not found.');
    }

    private function selectScsiController(array $devices): array
    {
        $controllerTypes = [
            'VirtualLsiLogicController',
            'VirtualLsiLogicSASController',
            'VirtualBusLogicController',
            'ParaVirtualSCSIController',
        ];

        foreach ($devices as $device) {
            if (!is_array($device) || !in_array($device['_xsi_type'] ?? '', $controllerTypes, true)) {
                continue;
            }

            return $device;
        }

        return [];
    }

    private function nextDiskUnitNumber(array $devices, int $controllerKey, array $reservedUnitNumbers = []): int
    {
        $used = $reservedUnitNumbers;
        foreach ($devices as $device) {
            if (
                is_array($device)
                && ($device['_xsi_type'] ?? '') === 'VirtualDisk'
                && (int) ($device['controllerKey'] ?? 0) === $controllerKey
                && isset($device['unitNumber'])
            ) {
                $used[] = (int) $device['unitNumber'];
            }
        }

        for ($unit = 0; $unit <= 15; $unit++) {
            if ($unit === 7) {
                continue;
            }
            if (!in_array($unit, $used, true)) {
                return $unit;
            }
        }

        throw new EsxiException('No available SCSI unit number.');
    }

    private function nextNegativeKey(array $devices, int $default): int
    {
        $min = 0;
        foreach ($devices as $device) {
            if (is_array($device) && isset($device['key'])) {
                $min = min($min, (int) $device['key']);
            }
        }

        return min($default, $min - 1);
    }

    private function defaultDiskPath(array $vmInfo, int $unitNumber, array $params): string
    {
        if (!empty($params['datastore'])) {
            $datastore = (string) $params['datastore'];
            $folder = (string) ($params['folder'] ?? $vmInfo['name'] ?? 'vm');

            return '[' . $datastore . '] ' . trim($folder, '/') . '/' . ($vmInfo['name'] ?? 'disk') . '_' . $unitNumber . '.vmdk';
        }

        $vmPath = (string) ($vmInfo['config.files.vmPathName'] ?? '');
        if (preg_match('/^\\[([^\\]]+)]\\s+(.+)\\/[^\\/]+\\.vmx$/', $vmPath, $matches) === 1) {
            return '[' . $matches[1] . '] ' . $matches[2] . '/' . ($vmInfo['name'] ?? 'disk') . '_' . $unitNumber . '.vmdk';
        }

        throw new \InvalidArgumentException('Missing add disk parameter: disk_path or datastore');
    }

    private function dataObjectFromDecoded(array $value, ?string $fallbackType = null): DataObject
    {
        $type = $value['_xsi_type'] ?? $fallbackType;
        unset($value['_xsi_type'], $value['_type']);

        $properties = [];
        foreach ($value as $name => $property) {
            if (is_array($property)) {
                if (array_is_list($property)) {
                    $properties[$name] = array_map(
                        fn (mixed $item): mixed => is_array($item) ? $this->dataObjectFromDecoded($item) : $item,
                        $property
                    );
                } else {
                    $properties[$name] = $this->dataObjectFromDecoded($property);
                }
                continue;
            }

            $properties[$name] = $property;
        }

        return $type === null ? DataObject::plain($properties) : DataObject::typed((string) $type, $properties);
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

    private function executeReconfigure(Mor $vm, array $configSpec, bool $wait = true): array
    {
        $task = $this->client->reconfigVMTask->execute(
            $vm,
            DataObject::typed('VirtualMachineConfigSpec', $configSpec)
        );

        return $this->taskResult($task, $wait);
    }
}
