<?php

namespace Tests\Unit;

use App\Services\Tournament\TournamentBracketPlannerService;
use PHPUnit\Framework\TestCase;

class TournamentBracketPlannerServiceTest extends TestCase
{
    public function test_resolve_match_sizes_for_common_scenarios(): void
    {
        $service = new TournamentBracketPlannerService();

        $this->assertSame([4, 4], $service->resolveMatchSizes(8, 4));
        $this->assertSame([4, 4, 4], $service->resolveMatchSizes(12, 4));
        $this->assertSame([3], $service->resolveMatchSizes(3, 4));
        $this->assertSame([3, 2], $service->resolveMatchSizes(5, 4));
        $this->assertSame([3, 3, 3], $service->resolveMatchSizes(9, 4));

        $this->assertSame([2, 2], $service->resolveMatchSizes(4, 2));
        $this->assertSame([3], $service->resolveMatchSizes(3, 2));
        $this->assertSame([3, 2], $service->resolveMatchSizes(5, 2));
    }
}
