<?php

namespace Tests\Unit;

use App\Services\RuntimeHelperSafetyScanner;
use App\Services\RuntimeHelperTester;
use Tests\TestCase;

class RuntimeHelperTesterTest extends TestCase
{
    public function test_blocks_unsafe_code(): void
    {
        $result = $this->tester()->run('return process.env.APP_KEY;', helperType: 'utility');

        $this->assertFalse($result['ok']);
        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('Safety scan failed', $result['error']);
    }

    public function test_blocks_real_payment_test(): void
    {
        $result = $this->tester()->run('return await faucetPaySend("user@example.com", 1, "USDT");', dryRun: false, helperType: 'payment');

        $this->assertFalse($result['ok']);
        $this->assertSame('Real payment tests are disabled in private beta.', $result['error']);
    }

    public function test_safe_helper_returns_actual_output(): void
    {
        $result = $this->tester()->run('return { greeting: "Hello " + params.name };', ['name' => 'Ada']);

        $this->assertTrue($result['ok']);
        $this->assertSame('passed', $result['status']);
        $this->assertSame(['greeting' => 'Hello Ada'], $result['actual_output']['helper_result']);
    }

    private function tester(): RuntimeHelperTester
    {
        return new RuntimeHelperTester(new RuntimeHelperSafetyScanner());
    }
}
