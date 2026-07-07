<?php

declare(strict_types=1);

namespace Cwx1227\Esxi\Soap;

use DOMElement;
use Cwx1227\Esxi\Value\ManagedObjectReference;

final class XmlDecoder
{
    public static function decode(DOMElement $element): mixed
    {
        $childElements = self::childElements($element);
        $morType = $element->getAttribute('type');

        if ($childElements === []) {
            $text = trim($element->textContent);

            if ($morType !== '') {
                return new ManagedObjectReference($morType, $text);
            }

            return self::castScalar($text);
        }

        $result = [];

        if ($morType !== '') {
            $result['_type'] = $morType;
        }

        $xsiType = $element->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'type');
        if ($xsiType !== '') {
            $result['_xsi_type'] = self::stripPrefix($xsiType);
        }

        foreach ($childElements as $child) {
            $name = $child->localName;
            $value = self::decode($child);

            if (!array_key_exists($name, $result)) {
                $result[$name] = $value;
                continue;
            }

            if (!is_array($result[$name]) || !array_is_list($result[$name])) {
                $result[$name] = [$result[$name]];
            }

            $result[$name][] = $value;
        }

        return $result;
    }

    /**
     * @return DOMElement[]
     */
    private static function childElements(DOMElement $element): array
    {
        $children = [];

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }

        return $children;
    }

    private static function castScalar(string $value): mixed
    {
        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if ($value !== '' && preg_match('/^-?(0|[1-9]\d*)$/', $value) === 1) {
            return (int) $value;
        }

        if ($value !== '' && is_numeric($value) && str_contains($value, '.')) {
            return (float) $value;
        }

        return $value;
    }

    private static function stripPrefix(string $value): string
    {
        $pos = strpos($value, ':');

        return $pos === false ? $value : substr($value, $pos + 1);
    }
}
