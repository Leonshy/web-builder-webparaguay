<?php

namespace Webparaguay\Schema;

/**
 * Deriva la forma de los formularios del CMS a partir de site.schema.json.
 *
 * El CMS NO tiene un formulario escrito a mano por tipo de sección: pregunta
 * acá qué campos tiene `content` para un `type` dado y con qué reglas.
 * Si el esquema cambia, el formulario cambia solo.
 */
final class SchemaIntrospector
{
    /** @var array<string,mixed> */
    private array $schema;

    public function __construct(?array $schema = null)
    {
        $this->schema = $schema ?? Schema::decoded();
    }

    /** Tipos de sección disponibles. @return array<int,string> */
    public function sectionTypes(): array
    {
        return $this->schema['$defs']['section']['properties']['type']['enum'] ?? [];
    }

    /** Variantes válidas para un tipo. @return array<int,string> */
    public function variantsFor(string $type): array
    {
        foreach ($this->schema['$defs']['section']['allOf'] ?? [] as $branch) {
            if (($branch['if']['properties']['type']['const'] ?? null) === $type) {
                return $branch['then']['properties']['variant']['enum'] ?? [];
            }
        }

        return [];
    }

    /** Campos del envelope común (label, title, subtitle, ...). @return array<int,array<string,mixed>> */
    public function envelopeFields(): array
    {
        $props = $this->schema['$defs']['section']['properties'] ?? [];
        $out = [];
        foreach (['label', 'title', 'subtitle', 'anchor', 'background', 'background_image'] as $name) {
            if (isset($props[$name])) {
                $out[] = $this->describe($name, $props[$name], false);
            }
        }

        return $out;
    }

    /** Campos de `content` para un tipo. @return array<int,array<string,mixed>> */
    public function contentFields(string $type): array
    {
        $def = $this->schema['$defs']["content_{$type}"] ?? null;
        if ($def === null) {
            return [];
        }

        return $this->objectFields($def);
    }

    /** @return array<int,array<string,mixed>> */
    private function objectFields(array $objectSchema): array
    {
        $required = $objectSchema['required'] ?? [];
        $out = [];
        foreach ($objectSchema['properties'] ?? [] as $name => $prop) {
            $out[] = $this->describe($name, $prop, in_array($name, $required, true));
        }

        return $out;
    }

    /** @return array<string,mixed> */
    private function describe(string $name, array $prop, bool $required): array
    {
        $prop = $this->resolve($prop);
        $ref = $prop['__ref'] ?? null;

        $field = [
            'name' => $name,
            'label' => $this->humanize($name),
            'required' => $required,
            'kind' => 'text',
        ];

        $refKind = match (true) {
            str_ends_with((string) $ref, '/color') => 'color',
            str_ends_with((string) $ref, '/icon') => 'icon',
            str_ends_with((string) $ref, '/image') => 'image',
            str_ends_with((string) $ref, '/button') => 'button',
            str_ends_with((string) $ref, '/richtext') => 'richtext',
            str_ends_with((string) $ref, '/item') => 'item',
            default => null,
        };

        if ($refKind !== null) {
            $field['kind'] = $refKind;
            if ($refKind === 'item') {
                $field['fields'] = $this->objectFields($this->schema['$defs']['item']);
            } elseif ($refKind === 'button') {
                $field['fields'] = $this->objectFields($this->schema['$defs']['button']);
            }

            return $field;
        }

        $type = $prop['type'] ?? 'string';

        if (isset($prop['enum'])) {
            $field['kind'] = 'enum';
            $field['enum'] = $prop['enum'];
            $field['default'] = $prop['default'] ?? null;

            return $field;
        }

        $field = match ($type) {
            'boolean' => [...$field, 'kind' => 'boolean', 'default' => $prop['default'] ?? false],
            'integer', 'number' => [...$field, 'kind' => 'number', 'min' => $prop['minimum'] ?? null, 'max' => $prop['maximum'] ?? null],
            'array' => [...$field, ...$this->describeArray($prop)],
            'object' => [...$field, 'kind' => 'object', 'fields' => $this->objectFields($prop)],
            default => [...$field, 'kind' => (($prop['maxLength'] ?? 0) > 160) ? 'textarea' : 'text', 'max' => $prop['maxLength'] ?? null],
        };

        return $field;
    }

    /** @return array<string,mixed> */
    private function describeArray(array $prop): array
    {
        $items = $this->resolve($prop['items'] ?? []);
        $ref = $items['__ref'] ?? null;

        $itemKind = match (true) {
            str_ends_with((string) $ref, '/item') => 'item',
            str_ends_with((string) $ref, '/button') => 'button',
            ($items['type'] ?? null) === 'object' => 'object',
            ($items['type'] ?? null) === 'string' && isset($items['enum']) => 'enum',
            default => 'text',
        };

        $out = [
            'kind' => 'list',
            'min' => $prop['minItems'] ?? null,
            'max' => $prop['maxItems'] ?? null,
            'item_kind' => $itemKind,
        ];

        if ($itemKind === 'item') {
            $out['item_fields'] = $this->objectFields($this->schema['$defs']['item']);
        } elseif ($itemKind === 'button') {
            $out['item_fields'] = $this->objectFields($this->schema['$defs']['button']);
        } elseif ($itemKind === 'object') {
            $out['item_fields'] = $this->objectFields($items);
        } elseif ($itemKind === 'enum') {
            $out['item_enum'] = $items['enum'];
        }

        return $out;
    }

    /**
     * Resuelve un `$ref` local un nivel, conservando la ruta en `__ref`.
     *
     * @return array<string,mixed>
     */
    private function resolve(array $prop): array
    {
        if (! isset($prop['$ref'])) {
            return $prop;
        }

        $ref = $prop['$ref'];
        $path = explode('/', ltrim(str_replace('#/', '', $ref), '/'));
        $node = $this->schema;
        foreach ($path as $segment) {
            $node = $node[$segment] ?? [];
        }

        return [...$node, '__ref' => $ref];
    }

    private function humanize(string $name): string
    {
        return ucfirst(str_replace('_', ' ', $name));
    }
}
