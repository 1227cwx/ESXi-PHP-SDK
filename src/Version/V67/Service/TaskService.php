<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Service;

use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;

final class TaskService extends AbstractService
{
    public function list(int $limit = 50): array
    {
        return $this->recent($limit);
    }

    public function recent(int $limit = 50): array
    {
        $row = $this->client->retrieveObjectProperties($this->client->service('taskManager'), 'TaskManager', [
            'recentTask',
        ]);

        $tasks = [];
        foreach (array_slice($this->client->vmwareArray($row['recentTask'] ?? [], 'ManagedObjectReference'), 0, $limit) as $task) {
            try {
                $tasks[] = $this->rawInfo(Mor::from($task, 'Task'));
            } catch (\Throwable) {
                continue;
            }
        }

        return $this->ok($tasks);
    }

    public function info(mixed $task): array
    {
        return $this->ok($this->rawInfo($this->client->resolveTask($task)));
    }

    public function wait(mixed $task, int $timeoutSeconds = 300, int $intervalMs = 1000): array
    {
        return $this->client->waitForTask($this->client->resolveTask($task), $timeoutSeconds, $intervalMs);
    }

    public function rawInfo(Mor $task, array $properties = []): array
    {
        $properties = $properties ?: [
            'info.key',
            'info.name',
            'info.descriptionId',
            'info.entity',
            'info.entityName',
            'info.state',
            'info.cancelled',
            'info.cancelable',
            'info.progress',
            'info.queueTime',
            'info.startTime',
            'info.completeTime',
            'info.error',
            'info.result',
        ];

        return $this->client->retrieveObjectProperties($task, 'Task', $properties);
    }
}
