<?php

namespace Tests\Unit;

use App\Models\AiOperation;
use Tests\TestCase;

class AiOperationCostTest extends TestCase
{
    public function test_it_estimates_input_and_output_cost_separately(): void
    {
        $operation = new AiOperation;
        config([
            'services.deepseek.input_cost_per_million' => 1.0,
            'services.deepseek.output_cost_per_million' => 2.0,
        ]);

        $this->assertSame(0.003, $operation->estimatedCost([
            'prompt_tokens' => 1000,
            'completion_tokens' => 1000,
        ]));
    }
}
