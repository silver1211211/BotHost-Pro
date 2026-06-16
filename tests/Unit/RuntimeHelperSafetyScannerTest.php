<?php

namespace Tests\Unit;

use App\Services\RuntimeHelperSafetyScanner;
use Tests\TestCase;

class RuntimeHelperSafetyScannerTest extends TestCase
{
    public function test_safe_code_passes(): void
    {
        $result = $this->scanner()->scan("const value = await getUserData('score', 0);\nreturn formatNumber(value);");

        $this->assertTrue($result['passed']);
    }

    public function test_process_is_blocked(): void
    {
        $result = $this->scanner()->scan('return process.env.APP_KEY;');

        $this->assertFalse($result['passed']);
        $this->assertSame('process', $result['blocked']);
    }

    public function test_require_is_blocked(): void
    {
        $result = $this->scanner()->scan("const fs = require('fs');");

        $this->assertFalse($result['passed']);
        $this->assertSame('require(', $result['blocked']);
    }

    public function test_internal_runtime_post_is_blocked(): void
    {
        $result = $this->scanner()->scan('return internalRuntimePost(url, payload, secret);');

        $this->assertFalse($result['passed']);
        $this->assertSame('internalRuntimePost', $result['blocked']);
    }

    public function test_protected_helper_name_is_blocked(): void
    {
        $result = $this->scanner()->validateHelperName('sendMessage');

        $this->assertFalse($result['passed']);
    }

    public function test_constructor_and_prototype_property_tricks_are_blocked(): void
    {
        $this->assertFalse($this->scanner()->scan('return value.constructor("return process")();')['passed']);
        $this->assertFalse($this->scanner()->scan('return value["prototype"];')['passed']);
        $this->assertFalse($this->scanner()->scan("return value['prototype'];")['passed']);
    }

    public function test_normal_helper_name_passes(): void
    {
        $result = $this->scanner()->validateHelperName('myCustomHelper');

        $this->assertTrue($result['passed']);
    }

    public function test_syntax_check_catches_invalid_javascript(): void
    {
        $result = $this->scanner()->syntaxCheck('const = ;');

        $this->assertFalse($result['passed']);
        $this->assertNotNull($result['exit_code']);
        $this->assertNotEmpty($result['error']);
    }

    private function scanner(): RuntimeHelperSafetyScanner
    {
        return new RuntimeHelperSafetyScanner();
    }
}
