<?php
/**
 * Unit tests for the riskiest parts of Oxyplug Preload: the .htaccess content
 * generation, the begin/end-marker strip, and font MIME detection.
 *
 * The target methods are private, so each is invoked through reflection on a
 * single shared OxyPreload instance.
 */

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtaccessTest extends TestCase
{
    private static OxyPreload $plugin;

    public static function setUpBeforeClass(): void
    {
        self::$plugin = new OxyPreload();
    }

    /**
     * Invoke a private/protected method on the plugin instance.
     *
     * @param mixed ...$args
     * @return mixed
     */
    private function call(string $method, ...$args)
    {
        $ref = new ReflectionMethod(OxyPreload::class, $method);
        if (PHP_VERSION_ID < 80100) {
            $ref->setAccessible(true);
        }

        return $ref->invokeArgs(self::$plugin, $args);
    }

    // ---- generate_htaccess_content() ------------------------------------

    public function test_generate_returns_empty_string_for_no_preloads(): void
    {
        $this->assertSame('', $this->call('generate_htaccess_content', array()));
    }

    public function test_generate_wraps_directives_in_filesmatch_and_ifmodule(): void
    {
        $content = $this->call('generate_htaccess_content', array(
            'script' => array('https://example.com/app.js'),
        ));

        $this->assertStringContainsString('<FilesMatch "index\.(html|htm|php)$">', $content);
        $this->assertStringContainsString('<IfModule mod_headers.c>', $content);
        $this->assertStringContainsString(
            'Header append Link "<https://example.com/app.js>; rel=preload; as=script"',
            $content
        );
    }

    public function test_generate_emits_one_link_header_per_url(): void
    {
        $content = $this->call('generate_htaccess_content', array(
            'style' => array(
                'https://example.com/a.css',
                'https://example.com/b.css',
            ),
        ));

        $this->assertSame(2, substr_count($content, 'Header append Link'));
        $this->assertStringContainsString('<https://example.com/a.css>; rel=preload; as=style', $content);
        $this->assertStringContainsString('<https://example.com/b.css>; rel=preload; as=style', $content);
    }

    public function test_generate_adds_type_and_crossorigin_for_known_font(): void
    {
        $content = $this->call('generate_htaccess_content', array(
            'font' => array('https://example.com/font.woff2'),
        ));

        $this->assertStringContainsString(
            '<https://example.com/font.woff2>; rel=preload; as=font; type=font/woff2; crossorigin',
            $content
        );
    }

    public function test_generate_omits_type_for_unknown_font_extension_but_keeps_crossorigin(): void
    {
        $content = $this->call('generate_htaccess_content', array(
            'font' => array('https://example.com/font.bin'),
        ));

        $this->assertStringNotContainsString('type=', $content);
        $this->assertStringContainsString(
            '<https://example.com/font.bin>; rel=preload; as=font; crossorigin',
            $content
        );
    }

    public function test_generate_does_not_add_crossorigin_for_non_font(): void
    {
        $content = $this->call('generate_htaccess_content', array(
            'script' => array('https://example.com/app.js'),
        ));

        $this->assertStringNotContainsString('crossorigin', $content);
    }

    // ---- font_mime_type() -----------------------------------------------

    #[DataProvider('fontMimeProvider')]
    public function test_font_mime_type(string $url, string $expected): void
    {
        $this->assertSame($expected, $this->call('font_mime_type', $url));
    }

    public static function fontMimeProvider(): array
    {
        return array(
            'woff2'                => array('https://example.com/f.woff2', 'font/woff2'),
            'woff'                 => array('https://example.com/f.woff', 'font/woff'),
            'ttf'                  => array('https://example.com/f.ttf', 'font/ttf'),
            'otf'                  => array('https://example.com/f.otf', 'font/otf'),
            'eot'                  => array('https://example.com/f.eot', 'application/vnd.ms-fontobject'),
            'uppercase extension'  => array('https://example.com/F.WOFF2', 'font/woff2'),
            'query string ignored' => array('https://example.com/f.woff2?v=123', 'font/woff2'),
            'unknown extension'    => array('https://example.com/f.svg', ''),
            'no extension'         => array('https://example.com/font', ''),
        );
    }

    // ---- strip_oxyplug_section() ----------------------------------------

    public function test_strip_removes_only_the_oxyplug_block(): void
    {
        $content = "# BEGIN WordPress\nRewriteEngine On\n# END WordPress\n\n"
            . "# BEGIN Oxyplug Preload\n  Header append Link \"<x>; rel=preload\"\n# END Oxyplug Preload\n";

        $stripped = $this->call('strip_oxyplug_section', $content);

        $this->assertStringNotContainsString('Oxyplug Preload', $stripped);
        $this->assertStringContainsString('# BEGIN WordPress', $stripped);
        $this->assertStringContainsString('RewriteEngine On', $stripped);
    }

    public function test_strip_is_noop_when_no_marker_present(): void
    {
        $content = "# BEGIN WordPress\nRewriteEngine On\n# END WordPress\n";

        $this->assertSame($content, $this->call('strip_oxyplug_section', $content));
    }

    public function test_strip_handles_block_with_no_surrounding_content(): void
    {
        $content = "# BEGIN Oxyplug Preload\nHeader append Link \"<x>\"\n# END Oxyplug Preload\n";

        $this->assertSame('', $this->call('strip_oxyplug_section', $content));
    }

    public function test_strip_removes_a_stale_block_so_regeneration_does_not_duplicate(): void
    {
        // Two writes must never leave two Oxyplug blocks behind.
        $content = "keep\n\n# BEGIN Oxyplug Preload\nold\n# END Oxyplug Preload\n";
        $stripped = $this->call('strip_oxyplug_section', $content);

        $this->assertSame(0, substr_count($stripped, '# BEGIN Oxyplug Preload'));
        $this->assertStringContainsString('keep', $stripped);
    }
}
