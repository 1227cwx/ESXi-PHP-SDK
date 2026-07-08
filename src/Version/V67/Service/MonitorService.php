<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Version\V67\Service;

use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference as Mor;

final class MonitorService extends AbstractService
{
    private const METRIC_ALIASES = [
        'cpu' => 'cpu.usage.average',
        'cpu_usage' => 'cpu.usage.average',
        'cpu_mhz' => 'cpu.usagemhz.average',
        'memory' => 'mem.usage.average',
        'memory_usage' => 'mem.usage.average',
        'memory_consumed' => 'mem.consumed.average',
        'disk' => 'disk.usage.average',
        'disk_usage' => 'disk.usage.average',
        'network' => 'net.usage.average',
        'net' => 'net.usage.average',
        'net_usage' => 'net.usage.average',
    ];

    public function vm(mixed $vm): array
    {
        return $this->ok($this->client->vps()->rawInfo($vm, [
            'name',
            'runtime.powerState',
            'summary.quickStats.overallCpuUsage',
            'summary.quickStats.overallCpuDemand',
            'summary.quickStats.hostMemoryUsage',
            'summary.quickStats.guestMemoryUsage',
            'summary.quickStats.uptimeSeconds',
            'summary.storage.committed',
            'summary.storage.uncommitted',
            'summary.guest.ipAddress',
            'summary.guest.toolsStatus',
        ]));
    }

    public function host(mixed $host = null): array
    {
        return $this->client->host()->performance($host);
    }

    public function storage(mixed $datastore = null): array
    {
        return $this->client->storage()->usage($datastore);
    }

    public function vmRealtime(mixed $vm, array $params = []): array
    {
        $params['range'] = $params['range'] ?? 'realtime';

        return $this->perf($this->client->resolveVirtualMachine($vm), $params);
    }

    public function hostRealtime(mixed $host = null, array $params = []): array
    {
        $params['range'] = $params['range'] ?? 'realtime';

        return $this->perf($this->client->resolveHost($host), $params);
    }

    public function vmHistory(mixed $vm, array $params = []): array
    {
        $params['range'] = $params['range'] ?? 'hour';

        return $this->perf($this->client->resolveVirtualMachine($vm), $params);
    }

    public function hostHistory(mixed $host = null, array $params = []): array
    {
        $params['range'] = $params['range'] ?? 'hour';

        return $this->perf($this->client->resolveHost($host), $params);
    }

    public function counters(): array
    {
        return $this->ok(array_values($this->performanceCounterMap()));
    }

    public function intervals(): array
    {
        $row = $this->client->retrieveObjectProperties($this->client->service('perfManager'), 'PerformanceManager', [
            'historicalInterval',
        ]);

        return $this->ok($this->client->vmwareArray($row['historicalInterval'] ?? [], 'PerfInterval'));
    }

    private function perf(Mor $entity, array $params = []): array
    {
        $this->assertAllowedParams($params, [
            'metrics',
            'range',
            'interval_seconds',
            'duration_seconds',
            'start_time',
            'end_time',
            'max_samples',
            'include_instances',
        ]);

        [$startTime, $endTime, $intervalSeconds, $maxSamples] = $this->timeOptions($params);
        $counterMap = $this->performanceCounterMap();
        $counterIds = $this->counterIds($params['metrics'] ?? ['cpu', 'memory', 'disk', 'network'], $counterMap);
        $metricIds = $this->metricIds(
            $entity,
            $counterIds,
            $startTime,
            $endTime,
            $intervalSeconds,
            $this->boolean($params['include_instances'] ?? false, 'include_instances')
        );

        if ($metricIds === []) {
            return $this->ok([
                'entity' => $entity->jsonSerialize(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'interval_seconds' => $intervalSeconds,
                'metrics' => [],
                'raw' => [],
            ]);
        }

        $querySpec = DataObject::typed('PerfQuerySpec', array_filter([
            'entity' => $entity,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'maxSample' => $maxSamples,
            'metricId' => array_map(
                static fn (array $metric): DataObject => DataObject::typed('PerfMetricId', $metric),
                $metricIds
            ),
            'intervalId' => $intervalSeconds,
        ], static fn (mixed $value): bool => $value !== null));

        $raw = $this->client->queryPerf->execute($this->client->service('perfManager'), [$querySpec]);

        return $this->ok([
            'entity' => $entity->jsonSerialize(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'interval_seconds' => $intervalSeconds,
            'metrics' => $this->normalizePerfResult($raw, $counterMap),
            'raw' => $raw,
        ]);
    }

    private function timeOptions(array $params): array
    {
        if (isset($params['range']) && !is_scalar($params['range'])) {
            throw new \InvalidArgumentException('Invalid parameter: range is not supported.');
        }

        $range = strtolower((string) ($params['range'] ?? 'hour'));
        $duration = match ($range) {
            'realtime', 'real_time', 'rt' => 3600,
            'hour', '1h' => 3600,
            'day', '24h' => 86400,
            default => throw new \InvalidArgumentException('Invalid parameter: range is not supported.'),
        };
        $interval = $range === 'day' || $range === '24h' ? 300 : 20;

        if (isset($params['duration_seconds'])) {
            $duration = $this->positiveInt($params['duration_seconds'], 'duration_seconds');
        }
        if (isset($params['interval_seconds'])) {
            $interval = $this->positiveInt($params['interval_seconds'], 'interval_seconds');
        }

        $explicitTimeRange = isset($params['start_time']) || isset($params['end_time']);
        $realtime = in_array($range, ['realtime', 'real_time', 'rt', 'hour', '1h'], true);
        $end = isset($params['end_time']) ? $this->dateTime($params['end_time'], 'end_time') : ($realtime && !$explicitTimeRange ? null : gmdate('Y-m-d\TH:i:s\Z'));
        $start = isset($params['start_time'])
            ? $this->dateTime($params['start_time'], 'start_time')
            : ($end === null ? null : gmdate('Y-m-d\TH:i:s\Z', strtotime($end) - $duration));

        $maxSamples = isset($params['max_samples'])
            ? $this->positiveInt($params['max_samples'], 'max_samples')
            : ($start === null && $end === null ? max(1, (int) ceil($duration / $interval)) : null);

        return [$start, $end, $interval, $maxSamples];
    }

    private function performanceCounterMap(): array
    {
        $row = $this->client->retrieveObjectProperties($this->client->service('perfManager'), 'PerformanceManager', [
            'perfCounter',
        ]);

        $map = [];
        foreach ($this->client->vmwareArray($row['perfCounter'] ?? [], 'PerfCounterInfo') as $counter) {
            if (!is_array($counter) || !isset($counter['key'])) {
                continue;
            }

            $name = $this->counterName($counter);
            if ($name === '') {
                continue;
            }

            $id = (int) $counter['key'];
            $map[$name] = [
                'id' => $id,
                'name' => $name,
                'group' => $counter['groupInfo']['key'] ?? null,
                'counter' => $counter['nameInfo']['key'] ?? null,
                'rollup' => $counter['rollupType'] ?? null,
                'unit' => $counter['unitInfo']['key'] ?? null,
                'label' => $counter['nameInfo']['label'] ?? null,
                'summary' => $counter['nameInfo']['summary'] ?? null,
            ];
            $map[(string) $id] = $map[$name];
        }

        return $map;
    }

    private function counterIds(mixed $metrics, array $counterMap): array
    {
        if (!is_array($metrics) && !is_string($metrics)) {
            throw new \InvalidArgumentException('Invalid parameter: metrics must be string or array.');
        }

        $items = is_array($metrics) ? $metrics : [$metrics];
        $ids = [];
        foreach ($items as $metric) {
            if (!is_scalar($metric)) {
                throw new \InvalidArgumentException('Invalid parameter: metrics contains unsupported value.');
            }

            $metric = strtolower(trim((string) $metric));
            if ($metric === '') {
                continue;
            }

            $name = self::METRIC_ALIASES[$metric] ?? $metric;
            if (!isset($counterMap[$name])) {
                throw new \InvalidArgumentException("Invalid parameter: metrics contains unsupported value {$metric}.");
            }

            $ids[] = (int) $counterMap[$name]['id'];
        }

        return array_values(array_unique($ids));
    }

    private function metricIds(Mor $entity, array $counterIds, ?string $startTime, ?string $endTime, int $intervalSeconds, bool $includeInstances): array
    {
        $available = $this->client->queryAvailablePerfMetric->execute(
            $this->client->service('perfManager'),
            $entity,
            $startTime,
            $endTime,
            $intervalSeconds
        );

        $selected = [];
        foreach ($counterIds as $counterId) {
            $matches = array_values(array_filter(
                $available,
                static fn (mixed $metric): bool => is_array($metric) && (int) ($metric['counterId'] ?? 0) === $counterId
            ));

            if ($matches === []) {
                $selected[] = ['counterId' => $counterId, 'instance' => ''];
                continue;
            }

            if ($includeInstances) {
                foreach ($matches as $match) {
                    $selected[] = [
                        'counterId' => $counterId,
                        'instance' => (string) ($match['instance'] ?? ''),
                    ];
                }
                continue;
            }

            $aggregate = null;
            foreach ($matches as $match) {
                if (($match['instance'] ?? '') === '') {
                    $aggregate = $match;
                    break;
                }
            }
            $metric = $aggregate ?? $matches[0];
            $selected[] = [
                'counterId' => $counterId,
                'instance' => (string) ($metric['instance'] ?? ''),
            ];
        }

        return $selected;
    }

    private function normalizePerfResult(array $raw, array $counterMap): array
    {
        $metrics = [];
        foreach ($raw as $entityMetric) {
            if (!is_array($entityMetric)) {
                continue;
            }

            $samples = $this->client->ensureList($entityMetric['sampleInfo'] ?? []);
            $series = $this->client->ensureList($entityMetric['value'] ?? []);

            foreach ($series as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $id = is_array($item['id'] ?? null) ? $item['id'] : [];
                $counterId = (int) ($id['counterId'] ?? 0);
                $counter = $counterMap[(string) $counterId] ?? ['id' => $counterId, 'name' => (string) $counterId];
                $values = $this->client->ensureList($item['value'] ?? []);
                $points = [];

                foreach ($values as $index => $value) {
                    $sample = is_array($samples[$index] ?? null) ? $samples[$index] : [];
                    $points[] = [
                        'time' => $sample['timestamp'] ?? null,
                        'interval_seconds' => $sample['interval'] ?? null,
                        'value' => $value,
                    ];
                }

                $metrics[] = [
                    'name' => $counter['name'] ?? (string) $counterId,
                    'counter_id' => $counterId,
                    'instance' => (string) ($id['instance'] ?? ''),
                    'unit' => $counter['unit'] ?? null,
                    'label' => $counter['label'] ?? null,
                    'points' => $points,
                ];
            }
        }

        return $metrics;
    }

    private function counterName(array $counter): string
    {
        $group = (string) ($counter['groupInfo']['key'] ?? '');
        $name = (string) ($counter['nameInfo']['key'] ?? '');
        $rollup = (string) ($counter['rollupType'] ?? '');

        return $group !== '' && $name !== '' && $rollup !== '' ? $group . '.' . $name . '.' . $rollup : '';
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

    private function dateTime(mixed $value, string $key): string
    {
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException("Invalid parameter: {$key} must be a valid datetime string.");
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            throw new \InvalidArgumentException("Invalid parameter: {$key} must be a valid datetime string.");
        }

        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    private function boolean(mixed $value, string $key): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && in_array($value, [0, 1], true)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => throw new \InvalidArgumentException("Invalid parameter: {$key} must be boolean."),
            };
        }

        throw new \InvalidArgumentException("Invalid parameter: {$key} must be boolean.");
    }
}
