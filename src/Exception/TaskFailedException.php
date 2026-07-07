<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Exception;

class TaskFailedException extends EsxiException
{
    public function __construct(
        string $message,
        private readonly array $taskInfo = []
    ) {
        parent::__construct($message);
    }

    public function getTaskInfo(): array
    {
        return $this->taskInfo;
    }
}
