<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Exception;

class SoapFaultException extends EsxiException
{
    public function __construct(
        private readonly string $faultCode,
        private readonly string $faultString,
        private readonly ?string $detail = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($this->buildMessage(), $code, $previous);
    }

    public function getFaultCode(): string
    {
        return $this->faultCode;
    }

    public function getFaultString(): string
    {
        return $this->faultString;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    private function buildMessage(): string
    {
        $message = trim($this->faultCode . ': ' . $this->faultString);

        if ($this->detail !== null && $this->detail !== '') {
            $message .= ' (' . $this->detail . ')';
        }

        return $message;
    }
}
