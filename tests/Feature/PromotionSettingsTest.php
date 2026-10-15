<?php

namespace Tests\Feature;

use App\Filament\Pages\PortalSettings;
use App\Models\SchoolClass;
use App\Models\SchoolSettings;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromotionSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);
    }

    #[Test]
    public function promotion_settings_default_to_manual_and_are_visible_in_portal_settings(): void
    {
        $settings = SchoolSettings::current();

        self::assertSame('manual', $settings->promotion_mode);
        self::assertNull($settings->promotion_min_average);
        self::assertNull($settings->promotion_min_subjects_passed);

        Livewire::test(PortalSettings::class)
            ->assertSet('data.promotion_mode', 'manual')
            ->assertSee('Promotion mode');
    }

    #[Test]
    public function results_admin_can_manage_promotion_settings_without_portal_switch_access(): void
    {
        $resultsAdmin = User::factory()->create();
        $resultsAdmin->assignRole('Results Admin');
        $this->actingAs($resultsAdmin);

        Livewire::test(PortalSettings::class)
            ->assertSee('Student promotion')
            ->assertDontSee('Student portal')
            ->set('data.promotion_mode', 'automatic')
            ->set('data.promotion_min_average', 70)
            ->set('data.promotion_min_subjects_passed', 6)
            ->call('save');

        self::assertSame('automatic', SchoolSettings::query()->firstOrFail()->promotion_mode);
        self::assertTrue((bool) SchoolSettings::query()->firstOrFail()->student_portal_enabled);
    }

    #[Test]
    public function automatic_promotion_requires_a_cumulative_average_and_pass_count_threshold(): void
    {
        $settings = SchoolSettings::current();
        $settings->promotion_mode = 'automatic';

        try {
            $settings->save();
            self::fail('Automatic mode must not save without configured thresholds.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('promotion_min_average', $exception->errors());
            self::assertArrayHasKey('promotion_min_subjects_passed', $exception->errors());
        }
    }

    #[Test]
    public function it_saves_valid_thresholds_and_a_next_class_mapping(): void
    {
        SchoolSettings::current()->fill([
            'promotion_mode' => 'automatic',
            'promotion_min_average' => 65,
            'promotion_min_subjects_passed' => 5,
        ])->save();

        $class = SchoolClass::query()->create(['name' => 'JSS 1', 'is_active' => true]);
        $nextClass = SchoolClass::query()->create(['name' => 'JSS 2', 'is_active' => true]);
        $class->update(['next_class_id' => $nextClass->id]);

        self::assertSame('automatic', SchoolSettings::current()->promotion_mode);
        self::assertSame(65.0, (float) SchoolSettings::current()->promotion_min_average);
        self::assertSame(5, (int) SchoolSettings::current()->promotion_min_subjects_passed);
        self::assertSame($nextClass->id, $class->refresh()->nextClass->id);
    }

    #[Test]
    public function a_class_cannot_be_its_own_next_class(): void
    {
        $class = SchoolClass::query()->create(['name' => 'JSS 1', 'is_active' => true]);
        $class->next_class_id = $class->id;

        $this->expectException(ValidationException::class);
        $class->save();
    }

    #[Test]
    public function class_progression_cannot_contain_a_cycle(): void
    {
        $first = SchoolClass::query()->create(['name' => 'JSS 1', 'is_active' => true]);
        $second = SchoolClass::query()->create(['name' => 'JSS 2', 'is_active' => true]);
        $second->update(['next_class_id' => $first->id]);
        $first->next_class_id = $second->id;

        $this->expectException(ValidationException::class);
        $first->save();
    }
}
