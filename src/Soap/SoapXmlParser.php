<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Soap;

use DOMDocument;
use DOMElement;
use Cwx1227\Esxi\Exception\EsxiException;
use Cwx1227\Esxi\Exception\SoapFaultException;

final class SoapXmlParser
{
    public function parse(string $xml, int $statusCode, array $headers = []): SoapResponse
    {
        $document = new DOMDocument();
        if (@$document->loadXML($xml) === false) {
            throw new EsxiException('Invalid XML response from ESXi, HTTP ' . $statusCode);
        }

        $fault = $this->firstElementByLocalName($document, 'Fault');
        if ($fault !== null) {
            throw new SoapFaultException(
                $this->childText($fault, 'faultcode'),
                $this->childText($fault, 'faultstring'),
                $this->childXml($fault, 'detail')
            );
        }

        if ($statusCode >= 400) {
            throw new EsxiException('ESXi SOAP request failed with HTTP ' . $statusCode);
        }

        return new SoapResponse($xml, $statusCode, $headers);
    }

    private function firstElementByLocalName(DOMDocument $document, string $localName): ?DOMElement
    {
        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->localName === $localName) {
                return $element;
            }
        }

        return null;
    }

    private function childText(DOMElement $element, string $localName): string
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $localName) {
                return trim($child->textContent);
            }
        }

        return '';
    }

    private function childXml(DOMElement $element, string $localName): ?string
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $localName) {
                $parts = [];
                foreach ($child->childNodes as $detailChild) {
                    $parts[] = $child->ownerDocument?->saveXML($detailChild) ?: '';
                }

                return trim(implode('', $parts)) ?: null;
            }
        }

        return null;
    }
}
