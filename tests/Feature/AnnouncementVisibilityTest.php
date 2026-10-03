<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnnouncementVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function only_published_announcements_within_their_live_window_are_public(): void
    {
        Announcement::query()->create(['title' => 'Live notice', 'body' => 'This notice is currently live.', 'status' => 'published', 'starts_at' => now()->subDay(), 'expires_at' => now()->addDay()]);
        Announcement::query()->create(['title' => 'Future notice', 'body' => 'This notice is not live yet.', 'status' => 'published', 'starts_at' => now()->addDay()]);
        Announcement::query()->create(['title' => 'Expired notice', 'body' => 'This notice has expired.', 'status' => 'published', 'starts_at' => now()->subDays(3), 'expires_at' => now()->subDay()]);
        Announcement::query()->create(['title' => 'Draft notice', 'body' => 'This notice is a draft.', 'status' => 'draft']);

        $this->get('/announcements')
            ->assertOk()
            ->assertSee('Live notice')
            ->assertDontSee('Future notice')
            ->assertDontSee('Expired notice')
            ->assertDontSee('Draft notice');
    }
}
