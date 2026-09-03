<?php

namespace App\Cms;

use App\Models\Cms\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webparaguay\Schema\SchemaValidator;

/**
 * Toda escritura del CMS valida contra site.schema.json ANTES de persistir.
 * Si no valida, no se guarda.
 *
 * `persistOrRollback` aplica los cambios dentro de una transacción, arma el
 * documento completo del sitio, lo valida y sólo hace commit si pasa.
 */
final class SiteValidator
{
    public function __construct(
        private SiteAssembler $assembler,
        private SchemaValidator $schema,
    ) {}

    /** @return array<int,string> */
    public function errorsFor(Site $site): array
    {
        return $this->schema->errors($this->assembler->toArray($site->load('pages.sections')));
    }

    /**
     * Ejecuta $mutation (que modifica el sitio en la base) y hace commit sólo si
     * el sitio resultante valida. Si no, revierte y lanza ValidationException.
     *
     * @template T
     * @param  \Closure():T  $mutation
     * @return T
     */
    public function persistOrRollback(Site $site, \Closure $mutation): mixed
    {
        return DB::transaction(function () use ($site, $mutation) {
            $result = $mutation();

            $errors = $this->errorsFor($site->refresh());
            if ($errors !== []) {
                throw ValidationException::withMessages([
                    'schema' => $errors,
                ]);
            }

            return $result;
        });
    }
}
