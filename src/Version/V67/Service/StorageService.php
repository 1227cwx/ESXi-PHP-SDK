<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Service;

use Cwx1227\Esxi\Exception\EsxiException;
use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;

final class StorageService extends AbstractService
{
    public function list(array $properties = []): array
    {
        return $this->ok($this->rows($properties));
    }

    public function rows(array $properties = []): array
    {
        $properties = $properties ?: [
            'name',
            'summary.name',
            'summary.type',
            'summary.url',
            'summary.capacity',
            'summary.freeSpace',
            'summary.uncommitted',
            'summary.accessible',
            'summary.maintenanceMode',
            'browser',
        ];

        return $this->client->retrieveByContainerView('Datastore', $properties);
    }

    public function info(mixed $datastore, array $properties = []): array
    {
        $properties = $properties ?: [
            'name',
            'summary',
            'info',
            'host',
            'vm',
            'browser',
        ];

        return $this->ok($this->client->retrieveObjectProperties(
            $this->client->resolveDatastore($datastore),
            'Datastore',
            $properties
        ));
    }

    public function usage(mixed $datastore = null): array
    {
        $rows = $datastore === null
            ? $this->rows()
            : [$this->client->retrieveObjectProperties($this->client->resolveDatastore($datastore), 'Datastore', [
                'name',
                'summary.name',
                'summary.type',
                'summary.capacity',
                'summary.freeSpace',
                'summary.uncommitted',
                'summary.accessible',
            ])];

        $items = [];
        foreach ($rows as $row) {
            $capacity = (int) ($row['summary.capacity'] ?? 0);
            $free = (int) ($row['summary.freeSpace'] ?? 0);
            $uncommitted = (int) ($row['summary.uncommitted'] ?? 0);
            $used = max(0, $capacity - $free);

            $items[] = [
                'name' => $row['name'] ?? $row['summary.name'] ?? null,
                'type' => $row['summary.type'] ?? null,
                'capacity_bytes' => $capacity,
                'free_bytes' => $free,
                'used_bytes' => $used,
                'uncommitted_bytes' => $uncommitted,
                'used_percent' => $capacity > 0 ? round($used / $capacity * 100, 2) : null,
                'accessible' => $row['summary.accessible'] ?? null,
                'mor' => $row['mor'] ?? null,
            ];
        }

        return $this->ok($datastore === null ? $items : ($items[0] ?? []));
    }

    public function files(string $datastorePath, array $params = [], bool $wait = true): array
    {
        $this->assertSearchParams($params);
        $this->assertDatastorePath($datastorePath, 'datastorePath');
        $browser = $this->resolveBrowser($params['datastore'] ?? $this->datastoreNameFromPath($datastorePath));
        $searchSpec = $this->buildSearchSpec($params);
        $recursive = (bool) ($params['recursive'] ?? false);
        $task = $recursive
            ? $this->client->searchDatastoreSubFoldersTask->execute($browser, $datastorePath, $searchSpec)
            : $this->client->searchDatastoreTask->execute($browser, $datastorePath, $searchSpec);

        return $this->taskResult($task, $wait, ['path' => $datastorePath, 'recursive' => $recursive]);
    }

    /** @internal */
    public function copyFile(string $sourceName, string $destinationName, bool $force = false, bool $wait = true, array $options = []): array
    {
        $this->assertCopyOptions($options);
        $this->assertDatastorePath($sourceName, 'sourceName');
        $this->assertDatastorePath($destinationName, 'destinationName');
        if ($sourceName === $destinationName) {
            throw new \InvalidArgumentException('Invalid parameter: sourceName and destinationName cannot be the same.');
        }

        if ($this->shouldCopyAsVirtualDisk($sourceName, $destinationName, $options)) {
            return $this->copyVirtualDisk($sourceName, $destinationName, $force, $wait, $options);
        }

        $task = $this->client->copyDatastoreFileTask->execute(
            $this->client->service('fileManager'),
            $sourceName,
            $destinationName,
            null,
            null,
            $force
        );

        return $this->taskResult($task, $wait, [
            'source' => $sourceName,
            'destination' => $destinationName,
            'force' => $force,
        ]);
    }

    /** @internal */
    public function copyVirtualDisk(string $sourceName, string $destinationName, bool $force = false, bool $wait = true, array $options = []): array
    {
        $this->assertCopyOptions($options);
        $this->assertDatastorePath($sourceName, 'sourceName');
        $this->assertDatastorePath($destinationName, 'destinationName');
        if ($sourceName === $destinationName) {
            throw new \InvalidArgumentException('Invalid parameter: sourceName and destinationName cannot be the same.');
        }

        $task = $this->client->copyVirtualDiskTask->execute(
            $this->client->service('virtualDiskManager'),
            $sourceName,
            $destinationName,
            null,
            null,
            null,
            $force
        );

        return $this->taskResult($task, $wait, [
            'source' => $sourceName,
            'destination' => $destinationName,
            'force' => $force,
            'virtual_disk' => true,
        ]);
    }

    /** @internal */
    public function makeDirectory(string $name, bool $createParentDirectories = true, array $options = []): array
    {
        $this->assertAllowedParams($options, []);
        $this->assertDatastorePath($name, 'name');

        $this->client->makeDirectory->execute(
            $this->client->service('fileManager'),
            $name,
            null,
            $createParentDirectories
        );

        return $this->ok([
            'path' => $name,
            'create_parent_directories' => $createParentDirectories,
        ]);
    }

    private function resolveBrowser(mixed $datastore): Mor
    {
        if ($datastore instanceof Mor && $datastore->type() === 'HostDatastoreBrowser') {
            return $datastore;
        }

        $row = $this->client->retrieveObjectProperties($this->client->resolveDatastore($datastore), 'Datastore', [
            'browser',
        ]);

        if (!isset($row['browser'])) {
            throw new EsxiException('Datastore browser not found.');
        }

        return Mor::from($row['browser'], 'HostDatastoreBrowser');
    }

    private function buildSearchSpec(array $params): DataObject
    {
        $inputDetails = array_replace([
            'file_type' => true,
            'file_size' => true,
            'modification' => true,
            'file_owner' => false,
        ], $params['details'] ?? []);
        $details = [
            'fileType' => (bool) $inputDetails['file_type'],
            'fileSize' => (bool) $inputDetails['file_size'],
            'modification' => (bool) $inputDetails['modification'],
            'fileOwner' => (bool) $inputDetails['file_owner'],
        ];

        $spec = [];
        if (!empty($params['file_types'])) {
            $query = [];
            foreach ((array) $params['file_types'] as $type) {
                $query[] = DataObject::typed($this->fileQueryType((string) $type));
            }
            $spec['query'] = $query;
        }

        $spec['details'] = DataObject::typed('FileQueryFlags', $details);

        if (array_key_exists('search_case_insensitive', $params)) {
            $spec['searchCaseInsensitive'] = (bool) $params['search_case_insensitive'];
        }

        if (!empty($params['match_pattern'])) {
            $spec['matchPattern'] = array_values((array) $params['match_pattern']);
        }

        $spec['sortFoldersFirst'] = (bool) ($params['sort_folders_first'] ?? true);

        return DataObject::typed('HostDatastoreBrowserSearchSpec', $spec);
    }

    private function assertSearchParams(array $params): void
    {
        $this->assertAllowedParams($params, [
            'datastore',
            'recursive',
            'file_types',
            'details',
            'search_case_insensitive',
            'match_pattern',
            'sort_folders_first',
        ]);

        if (isset($params['details'])) {
            if (!is_array($params['details'])) {
                throw new \InvalidArgumentException('Invalid parameter: details must be an array.');
            }
            $this->assertAllowedParams($params['details'], [
                'file_type',
                'file_size',
                'modification',
                'file_owner',
            ], 'details');
        }
    }

    private function assertCopyOptions(array $options): void
    {
        $this->assertAllowedParams($options, [
            'virtual_disk',
        ]);
    }

    private function fileQueryType(string $type): string
    {
        return match (strtolower($type)) {
            'folder' => 'FolderFileQuery',
            'vm', 'vmconfig', 'config' => 'VmConfigFileQuery',
            'disk', 'vmdk' => 'VmDiskFileQuery',
            'log' => 'LogFileQuery',
            'iso' => 'IsoImageFileQuery',
            'floppy' => 'FloppyImageFileQuery',
            default => throw new \InvalidArgumentException("Invalid parameter: file_types contains unsupported value {$type}."),
        };
    }

    private function datastoreNameFromPath(string $path): string
    {
        if (preg_match('/^\[([^\]]+)]/', $path, $matches) === 1) {
            return $matches[1];
        }

        throw new \InvalidArgumentException('Datastore path must be like "[datastore1] folder/file" or pass params["datastore"].');
    }

    private function assertDatastorePath(string $path, string $key): void
    {
        if (trim($path) === '' || preg_match('/^\[[^\]]+]($|\s+.+)/', $path) !== 1) {
            throw new \InvalidArgumentException("Invalid parameter: {$key} must be a datastore path like \"[datastore1] folder/file\".");
        }
    }

    private function shouldCopyAsVirtualDisk(string $sourceName, string $destinationName, array $options): bool
    {
        if (array_key_exists('virtual_disk', $options)) {
            return (bool) $options['virtual_disk'];
        }

        return str_ends_with(strtolower($sourceName), '.vmdk')
            && str_ends_with(strtolower($destinationName), '.vmdk');
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
}
