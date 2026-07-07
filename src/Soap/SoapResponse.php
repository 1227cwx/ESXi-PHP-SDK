<?php

declare(strict_types=1);

namespace WebmanVps\Esxi\Soap;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class SoapResponse
{
    private DOMXPath $xpath;

    public function __construct(
        private readonly string $xml,
        private readonly int $statusCode,
        private readonly array $headers = []
    ) {
        $document = new DOMDocument();
        $document->loadXML($xml);
        $this->xpath = new DOMXPath($document);
    }

    public function xml(): string
    {
        return $this->xml;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return DOMElement[]
     */
    public function returnElements(): array
    {
        $nodes = $this->xpath->query('/*[local-name()="Envelope"]/*[local-name()="Body"]/*[1]/*[local-name()="returnval"]');
        $elements = [];

        if ($nodes === false) {
            return $elements;
        }

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    public function firstReturnElement(): ?DOMElement
    {
        return $this->returnElements()[0] ?? null;
    }

    public function firstReturnValue(): mixed
    {
        $element = $this->firstReturnElement();

        return $element === null ? null : XmlDecoder::decode($element);
    }

    public function bodyFirstElement(): ?DOMElement
    {
        $nodes = $this->xpath->query('/*[local-name()="Envelope"]/*[local-name()="Body"]/*[1]');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);

        return $node instanceof DOMElement ? $node : null;
    }
}
