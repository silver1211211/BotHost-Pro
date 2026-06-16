<?php

namespace Tests\Unit;

use App\Models\RuntimeHelper;
use App\Models\RuntimeHelperCategory;
use App\Models\RuntimeHelperVersion;
use App\Services\RuntimeHelperBundleGenerator;
use App\Services\RuntimeHelperSafetyScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuntimeHelperBundleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_active_helper_set_generates_valid_empty_bundle(): void
    {
        $report = $this->generator()->generateContent();

        $this->assertTrue($report['ok']);
        $this->assertSame(0, $report['helpers_total']);
        $this->assertSame(0, $report['helpers_compiled']);
        $this->assertStringContainsString('return {};', $report['content']);
    }

    public function test_active_helper_compiles_into_bundle(): void
    {
        $this->createHelper('helloUser', 'return { ok: true, name: params.name || "User" };');

        $report = $this->generator()->generateContent();

        $this->assertSame(1, $report['helpers_total']);
        $this->assertSame(1, $report['helpers_compiled']);
        $this->assertStringContainsString('_adminHelpers["helloUser"] = async function(params)', $report['content']);
        $this->assertStringContainsString('const sendMessage = systemHelpers.sendMessage;', $report['content']);
        $this->assertStringNotContainsString('telegramBridgeSecret', $report['content']);
    }

    public function test_draft_helper_is_not_compiled(): void
    {
        $this->createHelper('draftThing', 'return true;', status: 'draft');

        $report = $this->generator()->generateContent();

        $this->assertSame(0, $report['helpers_total']);
        $this->assertStringNotContainsString('draftThing', $report['content']);
    }

    public function test_disabled_helper_is_not_compiled(): void
    {
        $this->createHelper('disabledThing', 'return true;', status: 'disabled');

        $report = $this->generator()->generateContent();

        $this->assertSame(0, $report['helpers_total']);
        $this->assertStringNotContainsString('disabledThing', $report['content']);
    }

    public function test_protected_helper_name_is_skipped(): void
    {
        $this->createHelper('sendMessage', 'return true;');

        $report = $this->generator()->generateContent();

        $this->assertSame(1, $report['helpers_total']);
        $this->assertSame(0, $report['helpers_compiled']);
        $this->assertSame(1, $report['helpers_skipped']);
        $this->assertStringContainsString('Protected helper name', $report['skipped'][0]['reason']);
    }

    public function test_unsafe_helper_code_is_skipped(): void
    {
        $this->createHelper('unsafeThing', 'return process.env.APP_KEY;');

        $report = $this->generator()->generateContent();

        $this->assertSame(1, $report['helpers_skipped']);
        $this->assertStringContainsString('Safety scan failed', $report['skipped'][0]['reason']);
        $this->assertStringNotContainsString('process.env', $report['content']);
    }

    public function test_invalid_javascript_helper_is_skipped(): void
    {
        $this->createHelper('brokenThing', 'const = ;');

        $report = $this->generator()->generateContent();

        $this->assertSame(1, $report['helpers_skipped']);
        $this->assertStringContainsString('Syntax check failed', $report['skipped'][0]['reason']);
        $this->assertStringNotContainsString('brokenThing', $report['content']);
    }

    public function test_publish_writes_live_file_after_temp_syntax_check_passes(): void
    {
        $this->createHelper('publishThing', 'return { ok: true };');
        $dir = storage_path('framework/testing/runtime-helper-bundle');
        $livePath = $dir.'/admin-helpers-generated.js';
        $tempPath = $dir.'/admin-helpers-generated.tmp.js';

        if (is_file($livePath)) {
            @unlink($livePath);
        }
        if (is_file($tempPath)) {
            @unlink($tempPath);
        }

        $report = $this->generator($livePath, $tempPath)->publish();

        $this->assertTrue($report['ok']);
        $this->assertFileExists($livePath);
        $this->assertFileDoesNotExist($tempPath);
        $this->assertStringContainsString('publishThing', file_get_contents($livePath));

        @unlink($livePath);
        @rmdir($dir);
    }

    private function generator(?string $livePath = null, ?string $tempPath = null): RuntimeHelperBundleGenerator
    {
        return new RuntimeHelperBundleGenerator(new RuntimeHelperSafetyScanner(), $livePath, $tempPath);
    }

    private function createHelper(string $name, string $code, string $status = 'active'): RuntimeHelper
    {
        $category = RuntimeHelperCategory::query()->create([
            'name' => 'Utility',
            'slug' => 'utility-'.strtolower($name),
            'helper_type' => 'utility',
            'allowed_domains' => [],
            'is_active' => true,
        ]);

        $helper = RuntimeHelper::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'label' => $name,
            'helper_type' => 'utility',
            'code' => $code,
            'status' => $status,
            'expose_to_bot_code' => true,
        ]);

        $version = RuntimeHelperVersion::query()->create([
            'helper_id' => $helper->id,
            'version_number' => 1,
            'code' => $code,
            'status' => $status,
        ]);

        $helper->forceFill(['active_version_id' => $version->id])->save();

        return $helper;
    }
}
