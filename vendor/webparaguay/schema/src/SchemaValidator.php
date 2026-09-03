<?php

namespace Webparaguay\Schema;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use RuntimeException;

/**
 * Valida un JSON de sitio contra site.schema.json.
 *
 * `site.schema.json` es la única fuente de verdad. Renderer, CMS y agentes se
 * derivan de él, nunca al revés. Toda escritura valida antes de persistir.
 */
final class SchemaValidator
{
    public function __construct(private ?string $schemaPath = null) {}

    private function schemaPath(): string
    {
        return $this->schemaPath ?? Schema::path();
    }

    /** @return array<int,string> lista de errores legibles; vacía si valida */
    public function errors(mixed $data): array
    {
        $schema = json_decode((string) file_get_contents($this->schemaPath()), false, flags: JSON_THROW_ON_ERROR);
        $normalized = json_decode(json_encode($data, JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);

        $result = (new Validator())->validate($normalized, $schema);

        if ($result->isValid()) {
            return [];
        }

        $formatted = (new ErrorFormatter())->format($result->error(), false);
        $errors = [];
        foreach ($formatted as $pointer => $messages) {
            foreach ((array) $messages as $message) {
                $errors[] = trim(($pointer === '/' ? '(raíz)' : $pointer).': '.$message);
            }
        }

        return $errors ?: ['El documento no valida contra el esquema.'];
    }

    public function passes(mixed $data): bool
    {
        return $this->errors($data) === [];
    }

    public function assertValid(mixed $data): void
    {
        $errors = $this->errors($data);
        if ($errors !== []) {
            throw new RuntimeException("El JSON del sitio no valida:\n - ".implode("\n - ", $errors));
        }
    }
}
