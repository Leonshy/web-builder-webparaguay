<?php

namespace Tests\Unit;

use App\Rendering\HtmlSanitizer;
use App\Rendering\IconRegistry;
use App\Rendering\Theme\Color;
use App\Rendering\Theme\ThemeHelper;
use App\Rendering\VideoEmbed;
use PHPUnit\Framework\TestCase;

class RenderingTest extends TestCase
{
    public function test_el_registro_de_iconos_usa_el_fallback_del_tipo_ante_una_clave_invalida(): void
    {
        $this->assertStringContainsString('<svg', IconRegistry::svg('no-existe', 'stats'));
        $this->assertFalse(IconRegistry::has('bootstrap-heart'));
        $this->assertTrue(IconRegistry::has('whatsapp'));
    }

    public function test_el_sanitizador_descarta_etiquetas_y_atributos_fuera_de_la_allowlist(): void
    {
        $dirty = '<p onclick="x()">hola <script>alert(1)</script><strong>mundo</strong></p><div>fuera</div>';
        $clean = (new HtmlSanitizer())->clean($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('<div', $clean);
        $this->assertStringContainsString('<strong>mundo</strong>', $clean);
        $this->assertStringContainsString('fuera', $clean);
    }

    public function test_el_sanitizador_solo_admite_hrefs_con_esquema_seguro(): void
    {
        $clean = (new HtmlSanitizer())->clean('<a href="javascript:alert(1)">x</a> <a href="https://a.com">y</a>');

        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('href="https://a.com"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $clean);
    }

    public function test_el_theme_deriva_los_colores_faltantes_y_garantiza_contraste_aa(): void
    {
        $vars = (new ThemeHelper([
            'colors' => ['primary' => '#1F6F5C', 'background' => '#FBFAF7', 'text' => '#1A1A1A'],
            'typography' => ['pairing' => 'playfair-inter'],
        ]))->cssVariables();

        $this->assertArrayHasKey('--wp-primary-dark', $vars);
        $this->assertArrayHasKey('--wp-border', $vars);

        $text = Color::fromHex($vars['--wp-text']);
        $bg = Color::fromHex($vars['--wp-bg']);
        $this->assertGreaterThanOrEqual(4.5, $text->contrastRatio($bg));

        $darkText = Color::fromHex($vars['--wp-dark-text']);
        $darkBg = Color::fromHex($vars['--wp-dark-bg']);
        $this->assertGreaterThanOrEqual(4.5, $darkText->contrastRatio($darkBg));
    }

    public function test_tipografia_desconocida_cae_a_la_lista_curada(): void
    {
        $vars = (new ThemeHelper(['colors' => ['primary' => '#000', 'background' => '#fff', 'text' => '#000'], 'typography' => ['pairing' => 'inexistente']]))->cssVariables();

        $this->assertNotEmpty($vars['--wp-font-heading']);
        $this->assertNotEmpty($vars['--wp-font-body']);
    }

    public function test_video_embed_parsea_youtube_y_vimeo_y_rechaza_lo_demas(): void
    {
        $yt = VideoEmbed::parse('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertSame('youtube', $yt['provider']);
        $this->assertStringContainsString('youtube-nocookie.com/embed/dQw4w9WgXcQ', $yt['embed_url']);

        $short = VideoEmbed::parse('https://youtu.be/dQw4w9WgXcQ');
        $this->assertSame('dQw4w9WgXcQ', $short['id']);

        $vimeo = VideoEmbed::parse('https://vimeo.com/123456789');
        $this->assertSame('vimeo', $vimeo['provider']);
        $this->assertStringContainsString('player.vimeo.com/video/123456789', $vimeo['embed_url']);

        $this->assertNull(VideoEmbed::parse('https://ejemplo.com/video.mp4'));
        $this->assertNull(VideoEmbed::parse(''));
        $this->assertNull(VideoEmbed::parse('no-es-una-url'));
    }
}
