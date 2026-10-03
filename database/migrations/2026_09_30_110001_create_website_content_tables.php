<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['mime_type', 'created_at']);
        });

        Schema::create('school_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('school_name')->default('Emmaculate Academy');
            $table->string('short_name')->default('Emmaculate Academy');
            $table->string('motto')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('address')->nullable();
            $table->string('phone_primary')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('office_hours')->nullable();
            $table->json('social_links')->nullable();
            $table->json('theme_tokens')->nullable();
            $table->json('verified_stats')->nullable();
            $table->unsignedTinyInteger('ca1_max_score')->default(40);
            $table->unsignedTinyInteger('ca2_max_score')->default(0);
            $table->unsignedTinyInteger('ca3_max_score')->default(0);
            $table->unsignedTinyInteger('exam_max_score')->default(60);
            $table->unsignedTinyInteger('pass_percentage')->default(40);
            $table->boolean('highlight_fail_grade')->default(true);
            $table->boolean('payment_gate_results')->default(false);
            $table->string('result_access_mode')->default('portal');
            $table->string('canonical_base_url')->nullable();
            $table->json('global_settings')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('eyebrow')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->json('content_blocks')->nullable();
            $table->foreignId('featured_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->foreignId('social_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hero_slides', function (Blueprint $table): void {
            $table->id();
            $table->string('eyebrow')->nullable();
            $table->string('heading');
            $table->text('body')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('mobile_image_path')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('secondary_cta_label')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        Schema::create('cms_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('cms_pages')->cascadeOnDelete();
            $table->string('section_key');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('content')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['page_id', 'section_key']);
        });

        Schema::create('academic_programmes', function (Blueprint $table): void {
            $table->id();
            $table->string('level')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('intro')->nullable();
            $table->json('approach')->nullable();
            $table->text('placeholder_note')->nullable();
            $table->json('curriculum')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        Schema::create('admissions_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('closed')->index();
            $table->text('intro')->nullable();
            $table->text('eligibility')->nullable();
            $table->json('process_steps')->nullable();
            $table->json('requirements')->nullable();
            $table->json('important_dates')->nullable();
            $table->text('screening_information')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->timestamps();
        });

        Schema::create('admission_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('applicant_name');
            $table->date('date_of_birth')->nullable();
            $table->string('applying_for');
            $table->string('guardian_name');
            $table->string('guardian_email')->nullable();
            $table->string('guardian_phone');
            $table->text('message')->nullable();
            $table->string('status')->default('submitted')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leadership_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->foreignId('photo_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('photo_path')->nullable();
            $table->text('biography')->nullable();
            $table->string('qualifications')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('news_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cover_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->foreignId('social_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('school_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->string('registration_url')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gallery_albums', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('cover_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('status')->default('published')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gallery_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('album_id')->constrained('gallery_albums')->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('category')->nullable();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('link_label')->nullable();
            $table->string('link_url')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
            $table->string('menu')->default('main')->index();
            $table->string('label');
            $table->string('url');
            $table->string('description')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('footer_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->json('links')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'contact_messages', 'footer_sections', 'navigation_items', 'announcements', 'faqs',
            'gallery_images', 'gallery_albums', 'school_events', 'news_posts', 'leadership_profiles',
            'admission_applications', 'admissions_settings', 'academic_programmes', 'cms_sections',
            'hero_slides', 'cms_pages', 'school_settings', 'media_assets',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
