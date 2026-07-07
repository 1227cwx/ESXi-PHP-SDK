<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Soap;

use XMLWriter;
use Cwx1227\Esxi\Value\DataObject;
use Cwx1227\Esxi\Value\ManagedObjectReference;

final class SoapXmlBuilder
{
    public function buildEnvelope(string $method, array $params): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('soapenv:Envelope');
        $xml->writeAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $xml->writeAttribute('xmlns:vim25', 'urn:vim25');
        $xml->startElement('soapenv:Body');
        $xml->startElement($method);
        $xml->writeAttribute('xmlns', 'urn:vim25');

        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }

            $this->writeElement($xml, (string) $name, $value);
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function writeElement(XMLWriter $xml, string $name, mixed $value): void
    {
        if (is_array($value) && array_is_list($value)) {
            foreach ($value as $item) {
                $this->writeElement($xml, $name, $item);
            }

            return;
        }

        $xml->startElement($name);

        if ($value instanceof ManagedObjectReference) {
            $xml->writeAttribute('type', $value->type());
            $xml->text($value->value());
            $xml->endElement();
            return;
        }

        if ($value instanceof DataObject) {
            if ($value->xsiType() !== null) {
                $xml->writeAttribute('xsi:type', $this->qName($value->xsiType()));
            }

            foreach ($value->properties() as $childName => $childValue) {
                if ($childValue === null) {
                    continue;
                }

                $this->writeElement($xml, (string) $childName, $childValue);
            }

            $xml->endElement();
            return;
        }

        if (is_array($value)) {
            foreach ($value as $childName => $childValue) {
                if ($childValue === null) {
                    continue;
                }

                $this->writeElement($xml, (string) $childName, $childValue);
            }

            $xml->endElement();
            return;
        }

        $xml->text(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
        $xml->endElement();
    }

    private function qName(string $type): string
    {
        return str_contains($type, ':') ? $type : 'vim25:' . $type;
    }
}
