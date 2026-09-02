<?php

namespace App\Cms;

use App\Rendering\IconRegistry;
use Webparaguay\Schema\SchemaIntrospector;

/**
 * Adapta SchemaIntrospector a lo que necesitan las vistas del CMS.
 * El CMS no tiene formularios escritos a mano por tipo: pregunta acá.
 */
final class SchemaForm
{
    public function __construct(private SchemaIntrospector $introspector) {}

    /** @return array<int,string> */
    public function sectionTypes(): array
    {
        return $this->introspector->sectionTypes();
    }

    /** @return array<int,string> */
    public function variantsFor(string $type): array
    {
        return $this->introspector->variantsFor($type);
    }

    /** @return array<int,array<string,mixed>> */
    public function envelopeFields(): array
    {
        return $this->introspector->envelopeFields();
    }

    /** @return array<int,array<string,mixed>> */
    public function contentFields(string $type): array
    {
        return $this->introspector->contentFields($type);
    }

    /** @return array<int,string> claves del registro de íconos */
    public function iconKeys(): array
    {
        return array_keys(IconRegistry::paths());
    }

    /**
     * Normaliza lo que llega del formulario: castea números y booleanos,
     * descarta strings vacíos y filas de lista marcadas para borrar.
     *
     * @param  mixed  $value
     * @param  array<string,mixed>  $field
     */
    public function coerce(mixed $value, array $field): mixed
    {
        return match ($field['kind']) {
            'number' => ($value === '' || $value === null) ? null : (str_contains((string) $value, '.') ? (float) $value : (int) $value),
            'boolean' => (bool) $value,
            'list' => $this->coerceList($value, $field),
            'item', 'button', 'object' => $this->coerceObject($value, $field['fields'] ?? []),
            'image' => $this->coerceImage($value),
            default => ($value === '' || $value === null) ? null : $value,
        };
    }

    /** @param array<string,mixed> $field */
    private function coerceList(mixed $value, array $field): array
    {
        if (! is_array($value)) {
            return [];
        }

        $kind = $field['item_kind'] ?? 'text';
        $itemFields = $field['item_fields'] ?? [];
        $out = [];

        foreach ($value as $row) {
            if (is_array($row) && ! empty($row['__remove'])) {
                continue;
            }

            $item = match ($kind) {
                'item', 'object' => $this->coerceObject($row, $itemFields),
                'button' => $this->coerceObject($row, $itemFields),
                default => ($row === '' ? null : $row),
            };

            if ($item !== null && $item !== []) {
                $out[] = $item;
            }
        }

        return array_values($out);
    }

    /** @param array<int,array<string,mixed>> $fields */
    private function coerceObject(mixed $value, array $fields): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        unset($value['__remove']);
        $out = [];
        foreach ($fields as $sub) {
            if (! array_key_exists($sub['name'], $value)) {
                continue;
            }
            $coerced = $this->coerce($value[$sub['name']], $sub);
            if ($coerced !== null && $coerced !== '' && $coerced !== []) {
                $out[$sub['name']] = $coerced;
            }
        }

        return $out === [] ? null : $out;
    }

    private function coerceImage(mixed $value): ?array
    {
        if (! is_array($value) || empty($value['src'])) {
            return null;
        }

        $out = ['src' => $value['src'], 'alt' => $value['alt'] ?? ''];
        if (! empty($value['focal']) && $value['focal'] !== 'center') {
            $out['focal'] = $value['focal'];
        }

        return $out;
    }
}
