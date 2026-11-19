<?php

namespace Tests\Unit\Support;

use App\Support\CspInlineScripts;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias para el generador de hashes criptográficos CSP de scripts inline críticos.
 */
class CspInlineScriptsTest extends TestCase
{
    #[Test]
    public function calcula_el_hash_sha256_en_formato_estandar_csp(): void
    {
        $script = "document.documentElement.classList.add('dark');";
        $expected = "'sha256-" . base64_encode(hash('sha256', $script, true)) . "'";

        $this->assertSame($expected, CspInlineScripts::hash($script));
        $this->assertMatchesRegularExpression("/^'sha256-[A-Za-z0-9+\/=]{44}'$/", CspInlineScripts::hash($script));
    }

    #[Test]
    public function resuelve_el_script_de_tema_publico_segun_modo_y_tema_inicial(): void
    {
        $this->assertSame(CspInlineScripts::PUBLIC_THEME_LIGHT, CspInlineScripts::publicThemeScript('light', 'dark'));
        $this->assertSame(CspInlineScripts::PUBLIC_THEME_DARK, CspInlineScripts::publicThemeScript('dark', 'light'));
        $this->assertSame(CspInlineScripts::PUBLIC_THEME_USER_CHOICE_LIGHT, CspInlineScripts::publicThemeScript('user_choice', 'light'));
        $this->assertSame(CspInlineScripts::PUBLIC_THEME_USER_CHOICE_DARK, CspInlineScripts::publicThemeScript('user_choice', 'dark'));
        $this->assertSame(CspInlineScripts::PUBLIC_THEME_USER_CHOICE_SYSTEM, CspInlineScripts::publicThemeScript('user_choice', 'system'));
    }

    #[Test]
    public function hashes_publicos_incluyen_todos_los_modos_de_tema_y_puente_de_contacto(): void
    {
        $hashes = CspInlineScripts::publicHashes();

        $this->assertCount(6, $hashes);
        $this->assertContains(CspInlineScripts::hash(CspInlineScripts::PUBLIC_THEME_DARK), $hashes);
        $this->assertContains(CspInlineScripts::hash(CspInlineScripts::PUBLIC_THEME_LIGHT), $hashes);
        $this->assertContains(CspInlineScripts::hash(CspInlineScripts::PUBLIC_THEME_USER_CHOICE_LIGHT), $hashes);
        $this->assertContains(CspInlineScripts::hash(CspInlineScripts::PUBLIC_THEME_USER_CHOICE_DARK), $hashes);
        $this->assertContains(CspInlineScripts::hash(CspInlineScripts::PUBLIC_THEME_USER_CHOICE_SYSTEM), $hashes);
        $this->assertContains(CspInlineScripts::hash(CspInlineScripts::CONTACT_BRIDGE), $hashes);
    }

    #[Test]
    public function hashes_de_administracion_extienden_los_publicos_con_el_tema_del_panel_sin_duplicados(): void
    {
        $adminHashes = CspInlineScripts::adminHashes();

        $this->assertCount(7, $adminHashes);
        $this->assertSame($adminHashes, array_values(array_unique($adminHashes)));
        $this->assertContains(CspInlineScripts::hash(CspInlineScripts::ADMIN_THEME), $adminHashes);
    }
}
