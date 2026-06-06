<?php

namespace Tests\Unit;

use App\Enums\PublicationStatus;
use Tests\TestCase;

class PublicationStatusTest extends TestCase
{
    public function test_enum_has_all_expected_cases(): void
    {
        $cases = PublicationStatus::cases();
        $names = array_map(fn ($c) => $c->name, $cases);

        $this->assertContains('Pending', $names);
        $this->assertContains('Processing', $names);
        $this->assertContains('Published', $names);
        $this->assertContains('Failed', $names);
        $this->assertCount(4, $cases);
    }

    public function test_pending_has_correct_string_value(): void
    {
        $this->assertSame('pending', PublicationStatus::Pending->value);
    }

    public function test_processing_has_correct_string_value(): void
    {
        $this->assertSame('processing', PublicationStatus::Processing->value);
    }

    public function test_published_has_correct_string_value(): void
    {
        $this->assertSame('published', PublicationStatus::Published->value);
    }

    public function test_failed_has_correct_string_value(): void
    {
        $this->assertSame('failed', PublicationStatus::Failed->value);
    }

    public function test_enum_is_backed_by_string(): void
    {
        $this->assertInstanceOf(PublicationStatus::class, PublicationStatus::from('pending'));
        $this->assertInstanceOf(PublicationStatus::class, PublicationStatus::from('processing'));
        $this->assertInstanceOf(PublicationStatus::class, PublicationStatus::from('published'));
        $this->assertInstanceOf(PublicationStatus::class, PublicationStatus::from('failed'));
    }

    public function test_tryFrom_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PublicationStatus::tryFrom('invalid'));
    }
}
