<?php

namespace Tests\V3;

use Tests\TestCase;

class PrintTemplateCompilationTest extends TestCase
{
    public function test_shared_print_template_compiles_to_valid_php(): void
    {
        // Inline @php followed by a later @endphp previously swallowed the
        // intervening document markup, breaking both preview and combined PDF.
        $source = file_get_contents(resource_path('views/v3/print-document-content.blade.php'));
        $compiled = app('blade.compiler')->compileString($source);
        $this->assertStringNotContainsString('@if(', $compiled);
        $this->assertStringNotContainsString('@foreach(', $compiled);
        $this->assertStringNotContainsString('@endphp', $compiled);
        token_get_all($compiled, TOKEN_PARSE);
    }
}
