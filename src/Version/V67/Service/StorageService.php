<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67\Service;

use WebmanVps\Esxi\Exception\EsxiException;
use WebmanVps\Esxi\Value\DataObject;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;

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
        $task = $this->client->copyDatastoreFileTask->execute(
            $this->client->service('fileManager'),
            $sourceName,
            $destinationName,
            $this->optionalMor($options['source_datacenter'] ?? null, 'Datacenter'),
            $this->optionalMor($options['destination_datacenter'] ?? null, 'Datacenter'),
            $force
        );

        return $this->taskResult($task, $wait, [
            'source' => $sourceName,
            'destination' => $destinationName,
            'force' => $force,
        ]);
    }

    /** @internal */
    public function makeDirectory(string $name, bool $createParentDirectories = true, array $options = []): array
    {
        $this->client->makeDirectory->execute(
            $this->client->service('fileManager'),
            $name,
            $this->optionalMor($options['datacenter'] ?? null, 'Datacenter'),
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
        $details = array_replace([
            'fileType' => true,
            'fileSize' => true,
            'modification' => true,
            'fileOwner' => false,
        ], $params['details'] ?? []);

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

    private function fileQueryType(string $type): string
    {
        return match (strtolower($type)) {
            'folder' => 'FolderFileQuery',
            'vm', 'vmconfig', 'config' => 'VmConfigFileQuery',
            'disk', 'vmdk' => 'VmDiskFileQuery',
            'log' => 'LogFileQuery',
            'iso' => 'IsoImageFileQuery',
            'floppy' => 'FloppyImageFileQuery',
            default => $type,
        };
    }

    private function datastoreNameFromPath(string $path): string
    {
        if (preg_match('/^\[([^\]]+)]/', $path, $matches) === 1) {
            return $matches[1];
        }

        throw new \InvalidArgumentException('Datastore path must be like "[datastore1] folder/file" or pass params["datastore"].');
    }

    private function optionalMor(mixed $value, string $type): ?Mor
    {
        return $value === null ? null : Mor::from($value, $type);
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
