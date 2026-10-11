<?php

namespace Tests\Feature;

use App\Mail\SchoolUpdateMail;
use App\Models\Announcement;
use App\Models\NewsPost;
use App\Models\NewsletterSubscriber;
use App\Models\ParentProfile;
use App\Models\SchoolEvent;
use App\Models\SchoolSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FriendlyAdminBehaviourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    #[Test]
    public function a_news_post_needs_no_web_address_or_publish_date(): void
    {
        $post = NewsPost::query()->create(['title' => 'Sports Day Was Great!', 'content' => 'It was a lovely day.', 'status' => 'published']);

        $this->assertSame('sports-day-was-great', $post->slug);
        $this->assertNotNull($post->published_at);
        $this->get('/news')->assertOk()->assertSee('Sports Day Was Great!');

        $second = NewsPost::query()->create(['title' => 'Sports Day Was Great!', 'content' => 'Again.', 'status' => 'draft']);
        $this->assertSame('sports-day-was-great-2', $second->slug);
    }

    #[Test]
    public function an_unpublished_announcement_is_hidden_and_a_published_one_is_shown(): void
    {
        Announcement::query()->create(['title' => 'Hidden notice', 'status' => 'draft']);
        Announcement::query()->create(['title' => 'Visible notice', 'status' => 'published']);

        $this->get('/')->assertOk()->assertSee('Visible notice')->assertDontSee('Hidden notice');
    }

    #[Test]
    public function new_posts_and_events_are_emailed_once_to_real_addresses_only(): void
    {
        Mail::fake();
        NewsletterSubscriber::query()->create(['email' => 'reader@example.com', 'status' => 'subscribed']);
        NewsletterSubscriber::query()->create(['email' => 'gone@example.com', 'status' => 'unsubscribed']);
        $parentUser = User::factory()->create(['email' => 'parent@example.com']);
        $parentUser->assignRole('Parent');
        ParentProfile::query()->create(['user_id' => $parentUser->id, 'full_name' => 'A Parent', 'email' => 'parent@example.com']);
        $student = User::factory()->create(['email' => 'student-1@students.emmaculateacademy.invalid']);
        $student->assignRole('Student');
        NewsPost::query()->create(['title' => 'Big News', 'content' => 'Details', 'status' => 'published']);
        SchoolEvent::query()->create(['title' => 'Open Day', 'starts_at' => now()->addWeek(), 'status' => 'published']);

        $this->artisan('school:send-notifications')->assertSuccessful();
        // two real addresses (the subscriber and the parent) x two items
        Mail::assertQueuedCount(4);

        $this->artisan('school:send-notifications')->assertSuccessful();
        Mail::assertQueuedCount(4);
    }

    #[Test]
    public function a_post_with_email_switched_off_is_not_emailed(): void
    {
        Mail::fake();
        NewsletterSubscriber::query()->create(['email' => 'reader@example.com', 'status' => 'subscribed']);
        NewsPost::query()->create(['title' => 'Quiet News', 'content' => 'x', 'status' => 'published', 'send_email' => false]);

        $this->artisan('school:send-notifications')->assertSuccessful();
        Mail::assertNothingQueued();
    }

    #[Test]
    public function unsubscribing_works_from_the_signed_link_only(): void
    {
        $subscriber = NewsletterSubscriber::query()->create(['email' => 'reader@example.com', 'status' => 'subscribed']);

        $this->get(route('newsletter.unsubscribe', $subscriber))->assertForbidden();
        $this->get(URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]))->assertOk();
        $this->assertSame('unsubscribed', $subscriber->fresh()->status);
    }

    #[Test]
    public function mission_pledge_and_anthem_written_in_school_profile_appear_on_the_website(): void
    {
        SchoolSettings::query()->orderBy('id')->first()->forceFill([
            'mission' => 'Our fresh mission statement.', 'pledge' => "I will be honest.\nI will work hard.", 'anthem' => "Verse one\nVerse two",
        ])->save();

        $this->get('/mission-vision')->assertOk()->assertSee('Our fresh mission statement.')->assertSee('I will work hard.')->assertSee('Verse two');
    }

    #[Test]
    public function the_contact_page_shows_a_map_once_a_gps_location_is_saved(): void
    {
        $this->get('/contact')->assertOk()->assertDontSee('openstreetmap.org', false);

        SchoolSettings::query()->orderBy('id')->first()->forceFill(['gps_location' => '7.4012,5.7345'])->save();
        $this->get('/contact')->assertOk()->assertSee('openstreetmap.org', false)->assertSee('Get directions');
    }

    #[Test]
    public function the_footer_credit_links_to_a_page_and_the_admin_link_can_be_hidden(): void
    {
        $this->get('/')->assertOk()->assertSee('Designed by Addigitaltech for Education')->assertSee('Admin login');
        $this->get('/designed-by')->assertOk()->assertSee('wa.me/2348110581449', false)->assertSee('addigitaltech.com.ng', false);

        SchoolSettings::query()->orderBy('id')->first()->forceFill(['show_admin_link' => false])->save();
        $this->get('/')->assertOk()->assertDontSee('Admin login');
    }
}
