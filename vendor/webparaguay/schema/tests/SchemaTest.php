<?php

namespace Webparaguay\Schema\Tests;

use PHPUnit\Framework\TestCase;
use Webparaguay\Schema\Schema;
use Webparaguay\Schema\SchemaIntrospector;
use Webparaguay\Schema\SchemaValidator;

class SchemaTest extends TestCase
{
    public function test_el_ejemplo_valida(): void
    {
        $data = json_decode((string) file_get_contents(Schema::examplePath()), true);
        $this->assertSame([], (new SchemaValidator())->errors($data));
    }

    public function test_un_documento_incompleto_no_valida(): void
    {
        $this->assertNotEmpty((new SchemaValidator())->errors(['schema_version' => '0.1']));
    }

    /** @return array<string,array<string,mixed>> */
    private function byName(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $out[$f['name']] = $f;
        }

        return $out;
    }

    public function test_el_introspector_deriva_variantes_y_campos_del_esquema(): void
    {
        $it = new SchemaIntrospector();

        $this->assertSame(['split', 'fullbg', 'minimal', 'carousel'], $it->variantsFor('hero'));
        $this->assertCount(14, $it->sectionTypes());

        $fields = $this->byName($it->contentFields('hero'));
        $this->assertSame('text', $fields['headline']['kind']);
        $this->assertSame(70, $fields['headline']['max']);
        $this->assertSame('button', $fields['primary_button']['kind']);
        $this->assertSame('image', $fields['image']['kind']);
        $this->assertSame('list', $fields['slides']['kind']);

        $stats = $this->byName($it->contentFields('stats'));
        $this->assertSame('list', $stats['items']['kind']);
        $this->assertSame('object', $stats['items']['item_kind']);

        $feature = $this->byName($it->contentFields('feature_list'));
        $this->assertSame('item', $feature['items']['item_kind']);
    }

    public function test_el_introspector_expone_el_envelope(): void
    {
        $names = array_column((new SchemaIntrospector())->envelopeFields(), 'name');
        $this->assertContains('label', $names);
        $this->assertContains('background', $names);
    }
}
