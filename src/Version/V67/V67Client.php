<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Version\V67;

use DOMElement;
use WebmanVps\Esxi\Exception\EsxiException;
use WebmanVps\Esxi\Exception\TaskFailedException;
use WebmanVps\Esxi\Soap\SoapExecutor;
use WebmanVps\Esxi\Soap\SoapResponse;
use WebmanVps\Esxi\Soap\XmlDecoder;
use WebmanVps\Esxi\Value\DataObject;
use WebmanVps\Esxi\Value\ManagedObjectReference as Mor;
use WebmanVps\Esxi\Version\V67\Operation\Diagnostic\BrowseDiagnosticLog;
use WebmanVps\Esxi\Version\V67\Operation\Diagnostic\QueryDescriptions;
use WebmanVps\Esxi\Version\V67\Operation\FileManager\CopyDatastoreFileTask;
use WebmanVps\Esxi\Version\V67\Operation\FileManager\MakeDirectory;
use WebmanVps\Esxi\Version\V67\Operation\Folder\CreateVMTask;
use WebmanVps\Esxi\Version\V67\Operation\Folder\RegisterVMTask;
use WebmanVps\Esxi\Version\V67\Operation\HostDatastoreBrowser\SearchDatastoreSubFoldersTask;
use WebmanVps\Esxi\Version\V67\Operation\HostDatastoreBrowser\SearchDatastoreTask;
use WebmanVps\Esxi\Version\V67\Operation\HostNetwork\AddPortGroup;
use WebmanVps\Esxi\Version\V67\Operation\HostNetwork\AddVirtualSwitch;
use WebmanVps\Esxi\Version\V67\Operation\HostNetwork\RemovePortGroup;
use WebmanVps\Esxi\Version\V67\Operation\HostNetwork\RemoveVirtualSwitch;
use WebmanVps\Esxi\Version\V67\Operation\HostNetwork\UpdatePortGroup;
use WebmanVps\Esxi\Version\V67\Operation\ManagedEntity\DestroyTask;
use WebmanVps\Esxi\Version\V67\Operation\Property\ContinueRetrievePropertiesEx;
use WebmanVps\Esxi\Version\V67\Operation\Property\CreateContainerView;
use WebmanVps\Esxi\Version\V67\Operation\Property\DestroyView;
use WebmanVps\Esxi\Version\V67\Operation\Property\RetrievePropertiesEx;
use WebmanVps\Esxi\Version\V67\Operation\Session\Login;
use WebmanVps\Esxi\Version\V67\Operation\Session\Logout;
use WebmanVps\Esxi\Version\V67\Operation\Session\RetrieveServiceContent;
use WebmanVps\Esxi\Version\V67\Operation\VirtualMachine\PowerOffVMTask;
use WebmanVps\Esxi\Version\V67\Operation\VirtualMachine\PowerOnVMTask;
use WebmanVps\Esxi\Version\V67\Operation\VirtualMachine\RebootGuest;
use WebmanVps\Esxi\Version\V67\Operation\VirtualMachine\ReconfigVMTask;
use WebmanVps\Esxi\Version\V67\Operation\VirtualMachine\ResetVMTask;
use WebmanVps\Esxi\Version\V67\Operation\VirtualMachine\ShutdownGuest;
use WebmanVps\Esxi\Version\V67\Operation\VirtualMachine\SuspendVMTask;
use WebmanVps\Esxi\Version\V67\Service\AuthService;
use WebmanVps\Esxi\Version\V67\Service\HostService;
use WebmanVps\Esxi\Version\V67\Service\InventoryService;
use WebmanVps\Esxi\Version\V67\Service\LogService;
use WebmanVps\Esxi\Version\V67\Service\MonitorService;
use WebmanVps\Esxi\Version\V67\Service\NetworkService;
use WebmanVps\Esxi\Version\V67\Service\StorageService;
use WebmanVps\Esxi\Version\V67\Service\TaskService;
use WebmanVps\Esxi\Version\V67\Service\VpsService;

final class V67Client
{
    private array $content = [];
    private ?array $session = null;

    private readonly RetrieveServiceContent $retrieveServiceContent;
    private readonly Login $loginOperation;
    private readonly Logout $logoutOperation;
    public readonly CreateContainerView $createContainerView;
    public readonly DestroyView $destroyView;
    private readonly RetrievePropertiesEx $retrievePropertiesEx;
    private readonly ContinueRetrievePropertiesEx $continueRetrievePropertiesEx;

    public readonly CreateVMTask $createVMTask;
    public readonly RegisterVMTask $registerVMTask;
    public readonly ReconfigVMTask $reconfigVMTask;
    public readonly PowerOnVMTask $powerOnVMTask;
    public readonly PowerOffVMTask $powerOffVMTask;
    public readonly ResetVMTask $resetVMTask;
    public readonly SuspendVMTask $suspendVMTask;
    public readonly ShutdownGuest $shutdownGuest;
    public readonly RebootGuest $rebootGuest;
    public readonly DestroyTask $destroyTask;

    public readonly AddVirtualSwitch $addVirtualSwitch;
    public readonly RemoveVirtualSwitch $removeVirtualSwitch;
    public readonly AddPortGroup $addPortGroup;
    public readonly UpdatePortGroup $updatePortGroup;
    public readonly RemovePortGroup $removePortGroup;

    public readonly CopyDatastoreFileTask $copyDatastoreFileTask;
    public readonly MakeDirectory $makeDirectory;
    public readonly SearchDatastoreTask $searchDatastoreTask;
    public readonly SearchDatastoreSubFoldersTask $searchDatastoreSubFoldersTask;
    public readonly BrowseDiagnosticLog $browseDiagnosticLog;
    public readonly QueryDescriptions $queryDescriptions;

    private ?AuthService $authService = null;
    private ?VpsService $vpsService = null;
    private ?HostService $hostService = null;
    private ?NetworkService $networkService = null;
    private ?MonitorService $monitorService = null;
    private ?InventoryService $inventoryService = null;
    private ?StorageService $storageService = null;
    private ?TaskService $taskService = null;
    private ?LogService $logService = null;

    public function __construct(
        private readonly SoapExecutor $soap,
        private readonly string $username,
        private readonly string $password,
        private readonly array $config = []
    ) {
        $this->retrieveServiceContent = new RetrieveServiceContent($soap);
        $this->loginOperation = new Login($soap);
        $this->logoutOperation = new Logout($soap);
        $this->createContainerView = new CreateContainerView($soap);
        $this->destroyView = new DestroyView($soap);
        $this->retrievePropertiesEx = new RetrievePropertiesEx($soap);
        $this->continueRetrievePropertiesEx = new ContinueRetrievePropertiesEx($soap);

        $this->createVMTask = new CreateVMTask($soap);
        $this->registerVMTask = new RegisterVMTask($soap);
        $this->reconfigVMTask = new ReconfigVMTask($soap);
        $this->powerOnVMTask = new PowerOnVMTask($soap);
        $this->powerOffVMTask = new PowerOffVMTask($soap);
        $this->resetVMTask = new ResetVMTask($soap);
        $this->suspendVMTask = new SuspendVMTask($soap);
        $this->shutdownGuest = new ShutdownGuest($soap);
        $this->rebootGuest = new RebootGuest($soap);
        $this->destroyTask = new DestroyTask($soap);

        $this->addVirtualSwitch = new AddVirtualSwitch($soap);
        $this->removeVirtualSwitch = new RemoveVirtualSwitch($soap);
        $this->addPortGroup = new AddPortGroup($soap);
        $this->updatePortGroup = new UpdatePortGroup($soap);
        $this->removePortGroup = new RemovePortGroup($soap);

        $this->copyDatastoreFileTask = new CopyDatastoreFileTask($soap);
        $this->makeDirectory = new MakeDirectory($soap);
        $this->searchDatastoreTask = new SearchDatastoreTask($soap);
        $this->searchDatastoreSubFoldersTask = new SearchDatastoreSubFoldersTask($soap);
        $this->browseDiagnosticLog = new BrowseDiagnosticLog($soap);
        $this->queryDescriptions = new QueryDescriptions($soap);
    }

    public function login(?string $locale = null): void
    {
        $this->content = $this->retrieveServiceContent->execute();
        $this->session = $this->loginOperation->execute(
            $this->service('sessionManager'),
            $this->username,
            $this->password,
            $locale
        );
    }

    public function logout(): void
    {
        if ($this->content === []) {
            return;
        }

        $this->logoutOperation->execute($this->service('sessionManager'));
    }

    public function about(): array
    {
        return $this->content['about'] ?? [];
    }

    public function session(): ?array
    {
        return $this->session;
    }

    public function auth(): AuthService
    {
        return $this->authService ??= new AuthService($this);
    }

    public function vps(): VpsService
    {
        return $this->vpsService ??= new VpsService($this);
    }

    public function host(): HostService
    {
        return $this->hostService ??= new HostService($this);
    }

    public function network(): NetworkService
    {
        return $this->networkService ??= new NetworkService($this);
    }

    public function monitor(): MonitorService
    {
        return $this->monitorService ??= new MonitorService($this);
    }

    public function inventory(): InventoryService
    {
        return $this->inventoryService ??= new InventoryService($this);
    }

    public function storage(): StorageService
    {
        return $this->storageService ??= new StorageService($this);
    }

    public function task(): TaskService
    {
        return $this->taskService ??= new TaskService($this);
    }

    public function tasks(): TaskService
    {
        return $this->task();
    }

    public function logs(): LogService
    {
        return $this->logService ??= new LogService($this);
    }

    public function service(string $name): Mor
    {
        if (!isset($this->content[$name])) {
            throw new EsxiException("ServiceContent field not available: {$name}");
        }

        return Mor::from($this->content[$name]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function retrieveByContainerView(string $type, array $properties): array
    {
        $view = $this->createContainerView->execute(
            $this->service('viewManager'),
            $this->service('rootFolder'),
            [$type],
            true
        );

        try {
            return $this->retrievePropertiesByView($view, $type, $properties);
        } finally {
            $this->destroyView->execute($view);
        }
    }

    public function retrieveObjectProperties(Mor $object, string $type, array $properties): array
    {
        $filter = DataObject::typed('PropertyFilterSpec', [
            'propSet' => DataObject::typed('PropertySpec', [
                'type' => $type,
                'pathSet' => array_values($properties),
            ]),
            'objectSet' => DataObject::typed('ObjectSpec', [
                'obj' => $object,
                'skip' => false,
            ]),
        ]);

        return $this->retrieveProperties([$filter])[0] ?? ['mor' => $object];
    }

    public function waitForTask(Mor $task, int $timeoutSeconds = 300, int $intervalMs = 1000): array
    {
        $deadline = time() + $timeoutSeconds;

        do {
            $info = $this->retrieveObjectProperties($task, 'Task', [
                'info.state',
                'info.error.localizedMessage',
                'info.error.fault',
                'info.result',
            ]);

            $state = $info['info.state'] ?? null;
            if ($state === 'success') {
                return [
                    'success' => true,
                    'task' => [
                        'id' => $task->value(),
                        'state' => 'success',
                    ],
                    'data' => $info,
                ];
            }

            if ($state === 'error') {
                $message = $info['info.error.localizedMessage'] ?? 'ESXi task failed.';
                throw new TaskFailedException((string) $message, $info);
            }

            usleep($intervalMs * 1000);
        } while (time() < $deadline);

        throw new EsxiException('Timed out waiting for ESXi task ' . $task->value());
    }

    public function resolveVirtualMachine(mixed $vm): Mor
    {
        if ($vm instanceof Mor) {
            return $vm;
        }

        if (is_array($vm) && isset($vm['mor'])) {
            return Mor::from($vm['mor'], 'VirtualMachine');
        }

        if (is_array($vm)) {
            return Mor::from($vm, 'VirtualMachine');
        }

        if (is_string($vm) && preg_match('/^(vm-)?\d+$/', $vm) === 1) {
            return new Mor('VirtualMachine', $vm);
        }

        if (is_string($vm)) {
            foreach ($this->vps()->rows(['name']) as $row) {
                if (($row['name'] ?? null) === $vm && isset($row['mor'])) {
                    return Mor::from($row['mor'], 'VirtualMachine');
                }
            }
        }

        throw new EsxiException('Virtual machine not found.');
    }

    public function resolveHost(mixed $host = null): Mor
    {
        if ($host instanceof Mor) {
            return $host;
        }

        if (is_array($host) && isset($host['mor'])) {
            return Mor::from($host['mor'], 'HostSystem');
        }

        if (is_string($host)) {
            if ($host === 'ha-host') {
                return new Mor('HostSystem', 'ha-host');
            }

            foreach ($this->host()->list(['name']) as $row) {
                if (($row['name'] ?? null) === $host && isset($row['mor'])) {
                    return Mor::from($row['mor'], 'HostSystem');
                }
            }
        }

        $hosts = $this->host()->list(['name']);
        if (isset($hosts[0]['mor'])) {
            return Mor::from($hosts[0]['mor'], 'HostSystem');
        }

        return new Mor('HostSystem', 'ha-host');
    }

    public function resolveDatastore(mixed $datastore = null): Mor
    {
        if ($datastore instanceof Mor) {
            return $datastore;
        }

        if (is_array($datastore) && isset($datastore['mor'])) {
            return Mor::from($datastore['mor'], 'Datastore');
        }

        if (is_array($datastore)) {
            return Mor::from($datastore, 'Datastore');
        }

        if (is_string($datastore) && preg_match('/^datastore-\d+$/', $datastore) === 1) {
            return new Mor('Datastore', $datastore);
        }

        $rows = $this->storage()->rows(['name', 'summary.name']);
        if (is_string($datastore)) {
            foreach ($rows as $row) {
                if (($row['name'] ?? $row['summary.name'] ?? null) === $datastore && isset($row['mor'])) {
                    return Mor::from($row['mor'], 'Datastore');
                }
            }
        }

        if ($datastore === null && isset($rows[0]['mor'])) {
            return Mor::from($rows[0]['mor'], 'Datastore');
        }

        throw new EsxiException('Datastore not found.');
    }

    public function resolveTask(mixed $task): Mor
    {
        if ($task instanceof Mor) {
            return $task;
        }

        if (is_array($task) && isset($task['mor'])) {
            return Mor::from($task['mor'], 'Task');
        }

        if (is_array($task)) {
            return Mor::from($task, 'Task');
        }

        if (is_string($task) && $task !== '') {
            return new Mor('Task', $task);
        }

        throw new EsxiException('Task not found.');
    }

    public function hostNetworkSystem(mixed $host = null): Mor
    {
        $row = $this->retrieveObjectProperties($this->resolveHost($host), 'HostSystem', [
            'configManager.networkSystem',
        ]);

        if (!isset($row['configManager.networkSystem'])) {
            throw new EsxiException('Host networkSystem manager not found.');
        }

        return Mor::from($row['configManager.networkSystem'], 'HostNetworkSystem');
    }

    public function vmwareArray(mixed $value, string $childKey): array
    {
        if (is_array($value) && array_key_exists($childKey, $value)) {
            return $this->ensureList($value[$childKey]);
        }

        return $this->ensureList($value);
    }

    public function ensureList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value) && array_is_list($value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function retrievePropertiesByView(Mor $view, string $type, array $properties): array
    {
        $filter = DataObject::typed('PropertyFilterSpec', [
            'propSet' => DataObject::typed('PropertySpec', [
                'type' => $type,
                'pathSet' => array_values($properties),
            ]),
            'objectSet' => DataObject::typed('ObjectSpec', [
                'obj' => $view,
                'skip' => true,
                'selectSet' => DataObject::typed('TraversalSpec', [
                    'name' => 'view',
                    'type' => 'ContainerView',
                    'path' => 'view',
                    'skip' => false,
                ]),
            ]),
        ]);

        return $this->retrieveProperties([$filter]);
    }

    /**
     * @param array<int,DataObject> $filterSpecs
     * @return array<int,array<string,mixed>>
     */
    private function retrieveProperties(array $filterSpecs): array
    {
        $response = $this->retrievePropertiesEx->execute(
            $this->service('propertyCollector'),
            $filterSpecs,
            (int) ($this->config['property_max_objects'] ?? 500)
        );

        $rows = $this->parseRetrievePropertiesResponse($response);
        $token = $this->retrieveToken($response);

        while ($token !== null && $token !== '') {
            $next = $this->continueRetrievePropertiesEx->execute(
                $this->service('propertyCollector'),
                $token
            );
            $rows = array_merge($rows, $this->parseRetrievePropertiesResponse($next));
            $token = $this->retrieveToken($next);
        }

        return $rows;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function parseRetrievePropertiesResponse(SoapResponse $response): array
    {
        $return = $response->firstReturnElement();
        if ($return === null) {
            return [];
        }

        $rows = [];
        foreach ($this->childElements($return, 'objects') as $objectContent) {
            $row = [];
            foreach ($this->childElements($objectContent) as $child) {
                if ($child->localName === 'obj') {
                    $mor = XmlDecoder::decode($child);
                    $row['mor'] = $mor;
                    if ($mor instanceof Mor) {
                        $row['moid'] = $mor->value();
                        $row['type'] = $mor->type();
                    }
                    continue;
                }

                if ($child->localName !== 'propSet') {
                    continue;
                }

                $name = '';
                $value = null;
                foreach ($this->childElements($child) as $propChild) {
                    if ($propChild->localName === 'name') {
                        $name = trim($propChild->textContent);
                    } elseif ($propChild->localName === 'val') {
                        $value = XmlDecoder::decode($propChild);
                    }
                }

                if ($name !== '') {
                    $row[$name] = $value;
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function retrieveToken(SoapResponse $response): ?string
    {
        $return = $response->firstReturnElement();
        if ($return === null) {
            return null;
        }

        foreach ($this->childElements($return, 'token') as $token) {
            return trim($token->textContent);
        }

        return null;
    }

    /**
     * @return DOMElement[]
     */
    private function childElements(DOMElement $element, ?string $localName = null): array
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if ($localName !== null && $child->localName !== $localName) {
                continue;
            }
            $children[] = $child;
        }

        return $children;
    }
}
