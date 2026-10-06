<?php

namespace Database\Seeders;

use App\Domain\Website\Support\SiteSections;
use App\Models\AffectiveTrait;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\FooterSection;
use App\Models\HeroSlide;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\PortalLink;
use App\Models\SchoolSettings;
use Illuminate\Database\Seeder;

/**
 * Brings the public website content in line with the approved designs.
 *
 * Safe to run more than once: new content is only created when missing, and the
 * one-time adjustments (renames, re-ordering, history layout) run a single time so
 * later edits made in the admin panel are never overwritten.
 */
class DesignRefreshSeeder extends Seeder
{
    private const MARKER = 'design_refresh_2026_10';

    public function run(): void
    {
        $settings = SchoolSettings::current();
        $firstRun = ! filled(data_get($settings->global_settings, self::MARKER));

        $this->sections();
        $this->branding($settings);
        $this->portalLinks($firstRun);
        $this->footer($firstRun);
        $this->navigation($firstRun);
        $this->heroSlide($firstRun);

        $this->once('results_defaults_2026_10', $settings, fn () => $this->affectiveTraits());

        if ($firstRun) {
            $this->history();
            $global = is_array($settings->global_settings) ? $settings->global_settings : [];
            $global[self::MARKER] = now()->toDateString();
            $settings->forceFill(['global_settings' => $global])->save();
        }
    }

    /** Run a block a single time, remembered in the global settings so admin deletions are respected. */
    private function once(string $key, SchoolSettings $settings, callable $callback): void
    {
        $settings->refresh();
        $global = is_array($settings->global_settings) ? $settings->global_settings : [];
        if (filled($global[$key] ?? null)) {
            return;
        }
        $callback();
        $global[$key] = now()->toDateString();
        $settings->forceFill(['global_settings' => $global])->save();
    }

    private function affectiveTraits(): void
    {
        $traits = ['Attendance', 'Attentiveness', 'Honesty', 'Industriousness', 'Initiative', 'Neatness', 'Relationship with others', 'Handwriting', 'Punctuality'];
        foreach ($traits as $index => $name) {
            AffectiveTrait::query()->firstOrCreate(['name' => $name], ['category' => 'affective', 'is_active' => true, 'sort_order' => $index + 1]);
        }
    }

    /** Fill empty branding fields only; anything set in the admin is never overwritten. */
    private function branding(SchoolSettings $settings): void
    {
        $updates = [];
        if (! filled($settings->logo_path) && is_file(storage_path('app/public/migrated-images/logo.jpg'))) {
            $updates['logo_path'] = 'migrated-images/logo.jpg';
        }
        if (! filled($settings->favicon_path) && is_file(storage_path('app/public/migrated-images/logo.jpg'))) {
            $updates['favicon_path'] = 'migrated-images/logo.jpg';
        }
        if (! filled($settings->description)) {
            $updates['description'] = 'Emmaculate Academy is a school in Arigidi Akoko, Ondo State, raising a morally upright generation of future leaders through sound academics and strong values.';
        }
        if ($updates !== []) {
            $settings->forceFill($updates)->save();
        }
    }

    private function media(string $filename): ?int
    {
        return MediaAsset::query()->where('path', 'migrated-images/'.$filename)->value('id');
    }

    private function sections(): void
    {
        $defaults = [
            'about.values' => ['title' => 'Our Core Values', 'description' => null, 'content' => [
                ['icon' => 'shield', 'title' => 'Integrity', 'text' => 'We uphold honesty and strong moral principles.'],
                ['icon' => 'book', 'title' => 'Excellence', 'text' => 'We pursue the highest standards in everything we do.'],
                ['icon' => 'handshake', 'title' => 'Respect', 'text' => 'We treat everyone with dignity, fairness and respect.'],
                ['icon' => 'lightbulb', 'title' => 'Discipline', 'text' => 'We promote self-control, responsibility and good conduct.'],
                ['icon' => 'handHeart', 'title' => 'Service', 'text' => 'We inspire a heart of service to God and humanity.'],
                ['icon' => 'globe', 'title' => 'Teamwork', 'text' => 'We work together towards common goals.'],
            ]],
            'about.why' => ['title' => null, 'description' => null, 'content' => [
                ['icon' => 'cap', 'title' => 'Quality Education', 'text' => 'Dedicated teachers committed to quality teaching and learning.'],
                ['icon' => 'shield', 'title' => 'Strong Moral Foundation', 'text' => 'A continuous moral and academic foundation from the early years through secondary school.'],
                ['icon' => 'users', 'title' => 'Character Building', 'text' => 'We build strong values, confidence and leadership.'],
                ['icon' => 'flask', 'title' => 'Practical Learning', 'text' => 'Hands-on activities, including laboratory work, support classroom learning.'],
                ['icon' => 'building', 'title' => 'Conducive Environment', 'text' => 'A serene, safe and supportive environment for learning and growth.'],
            ]],
            'academics.why' => ['title' => null, 'description' => null, 'content' => [
                ['icon' => 'users', 'title' => 'Dedicated Teachers', 'text' => 'Educators committed to helping every learner succeed.'],
                ['icon' => 'flask', 'title' => 'Practical Learning', 'text' => 'Hands-on activities, including laboratory work, support classroom learning.'],
                ['icon' => 'award', 'title' => 'Holistic Education', 'text' => 'We nurture academic, moral, physical and spiritual development.'],
                ['icon' => 'trendingUp', 'title' => 'Continuous Assessment', 'text' => 'Regular tests and assignments help us monitor each student\'s progress.'],
                ['icon' => 'heart', 'title' => 'Student Support', 'text' => 'Guidance and mentorship for personal and academic growth.'],
            ]],
            'academics.curriculum' => ['title' => null, 'description' => null, 'content' => [
                ['icon' => 'checkCircle', 'title' => 'Nigerian National Curriculum', 'text' => 'We follow the approved Nigerian curriculum.'],
                ['icon' => 'checkCircle', 'title' => 'Continuous Assessment', 'text' => 'Regular tests, assignments and projects to monitor student progress.'],
                ['icon' => 'checkCircle', 'title' => 'Practical & Hands-on Learning', 'text' => 'Laboratory work and real-life applications.'],
            ]],
            'academics.approach' => ['title' => null, 'description' => 'We focus on student-centred learning that encourages curiosity, creativity and collaboration.', 'content' => [
                ['title' => 'Interactive teaching methods'],
                ['title' => 'Critical thinking and problem solving'],
                ['title' => 'Character and values education'],
                ['title' => 'Safe, inclusive and supportive environment'],
            ]],
            'admissions.steps' => ['title' => null, 'description' => null, 'content' => [
                ['icon' => 'fileText', 'title' => 'Apply Online', 'text' => 'Fill out the application form with accurate details about the applicant.'],
                ['icon' => 'folder', 'title' => 'Document Submission', 'text' => 'Submit the required documents for review by the admissions office.'],
                ['icon' => 'clipboard', 'title' => 'Assessment / Interview', 'text' => 'Applicants may be invited for an assessment or interview as appropriate.'],
                ['icon' => 'send', 'title' => 'Admission Decision', 'text' => 'Successful applicants will receive an admission offer.'],
                ['icon' => 'shield', 'title' => 'Enrollment', 'text' => 'Accept the offer and complete the enrollment process to secure your place.'],
            ]],
            'admissions.levels' => ['title' => 'Academic Levels', 'description' => null, 'content' => [
                ['title' => 'Day-care & Nursery', 'text' => 'A joyful beginning where learning through play builds confident learners.', 'media_id' => $this->media('students-group.jpg'), 'url' => '/academics/primary'],
                ['title' => 'Primary School', 'text' => 'Building strong foundations in literacy, numeracy and character.', 'media_id' => $this->media('students-group.jpg'), 'url' => '/academics/primary'],
                ['title' => 'Junior Secondary (JSS 1 - 3)', 'text' => 'Preparing students for senior secondary with a well-rounded education.', 'media_id' => $this->media('textbooks.jpg'), 'url' => '/academics/secondary'],
                ['title' => 'Senior Secondary (SS 1 - 3)', 'text' => 'Advanced learning that prepares students for tertiary education and beyond.', 'media_id' => $this->media('students-reading.jpg'), 'url' => '/academics/secondary'],
            ]],
            'cta.admissions' => ['title' => 'Give Your Child a Stronger Foundation', 'description' => 'Join us today and experience the difference.', 'content' => []],
            'cta.join' => ['title' => 'Ready to Join Our Family?', 'description' => 'Give your child the best start for a successful future.', 'content' => []],
            'cta.academics' => ['title' => 'Empowering Minds. Shaping Leaders.', 'description' => 'We are committed to raising morally upright leaders through quality education and strong values.', 'content' => []],
            'mission.closing' => ['title' => null, 'description' => 'Our mission gives us direction, our vision gives us purpose, our pledge keeps us grounded, and our anthem unites us as one family.', 'content' => []],
        ];

        $order = 0;
        foreach ($defaults as $key => $values) {
            if (! array_key_exists($key, SiteSections::KEYS)) {
                continue;
            }
            CmsSection::query()->firstOrCreate(
                ['page_id' => null, 'section_key' => $key],
                $values + ['is_visible' => true, 'sort_order' => $order++],
            );
        }
    }

    private function portalLinks(bool $firstRun): void
    {
        $links = [
            ['key' => 'results', 'title' => 'Results Portal', 'description' => 'Check your results online', 'url' => '/login', 'sort_order' => 1],
            ['key' => 'admission', 'title' => 'Admission Portal', 'description' => 'Apply for admission online', 'url' => '/admissions', 'sort_order' => 2],
            ['key' => 'students', 'title' => 'Student Portal', 'description' => 'Access student resources', 'url' => '/login', 'sort_order' => 3],
            ['key' => 'staff', 'title' => 'Staff Portal', 'description' => 'Access staff resources', 'url' => '/login', 'sort_order' => 4],
            ['key' => 'parents', 'title' => 'Parent Portal', 'description' => 'Access parent resources', 'url' => '/login', 'sort_order' => 5],
        ];
        foreach ($links as $link) {
            $existing = PortalLink::query()->where('key', $link['key'])->first();
            if (! $existing) {
                PortalLink::query()->create($link + ['status' => 'live']);
            } elseif ($firstRun) {
                $existing->fill(['title' => $link['title'], 'description' => $link['description'], 'sort_order' => $link['sort_order']])->save();
            }
        }
        if ($firstRun) {
            PortalLink::query()->whereIn('key', ['teachers', 'school-staff'])->update(['status' => 'disabled']);
        }
    }

    private function footer(bool $firstRun): void
    {
        $sections = [
            ['Quick Links', [['label' => 'Home', 'url' => '/'], ['label' => 'About Us', 'url' => '/about'], ['label' => 'Academics', 'url' => '/academics'], ['label' => 'Admissions', 'url' => '/admissions'], ['label' => 'News & Events', 'url' => '/news'], ['label' => 'Gallery', 'url' => '/gallery'], ['label' => 'Contact Us', 'url' => '/contact']], 0],
            ['Portals', [['label' => 'Results Portal', 'url' => '/login'], ['label' => 'Admission Portal', 'url' => '/admissions'], ['label' => 'Student Portal', 'url' => '/login'], ['label' => 'Staff Portal', 'url' => '/login'], ['label' => 'Parent Portal', 'url' => '/login']], 1],
        ];
        foreach ($sections as [$title, $links, $sort]) {
            FooterSection::query()->firstOrCreate(['title' => $title], ['links' => $links, 'is_visible' => true, 'sort_order' => $sort]);
        }
        if ($firstRun) {
            FooterSection::query()->whereIn('title', ['Explore', 'Admissions', 'School Portal'])->update(['is_visible' => false]);
        }
    }

    private function navigation(bool $firstRun): void
    {
        if (! $firstRun) {
            return;
        }
        NavigationItem::query()->where('menu', 'main')->where('label', 'About')->update(['label' => 'About Us']);
        NavigationItem::query()->where('menu', 'main')->where('label', 'Mission, Vision & Pledge')->update(['label' => 'Mission, Vision, Pledge & Anthem']);
        NavigationItem::query()->where('menu', 'main')->where('label', 'Contact')->update(['label' => 'Contact']);
        NavigationItem::query()->where('menu', 'main')->where('label', 'FAQ')->update(['is_visible' => false]);
    }

    private function heroSlide(bool $firstRun): void
    {
        $heading = 'Raising Future Leaders with Integrity, Knowledge & Purpose';
        if (HeroSlide::query()->where('heading', $heading)->exists()) {
            return;
        }
        $imageId = $this->media('textbooks.jpg');
        $path = $imageId ? 'migrated-images/textbooks.jpg' : null;
        HeroSlide::query()->increment('sort_order');
        HeroSlide::query()->create([
            'eyebrow' => null,
            'heading' => $heading,
            'body' => 'A citadel of learning, raising morally upright leaders through quality education and strong values.',
            'image_id' => $imageId,
            'image_path' => $path,
            'image_alt' => 'Students of Emmaculate Academy holding their textbooks',
            'cta_label' => 'Discover More',
            'cta_url' => '/about',
            'is_enabled' => true,
            'sort_order' => 0,
        ]);
    }

    private function history(): void
    {
        $page = CmsPage::query()->where('slug', 'history')->first();
        if (! $page) {
            return;
        }
        $page->content_blocks = [
            ['type' => 'section', 'text' => 'A brief history of Emmaculate Academy, Olokun, Agbaluku Arigidi'],
            ['type' => 'paragraph', 'text' => 'Emmaculate Academy, the secondary school arm of Emmaculate Group of Schools, was established on 4th September 2018, at the beginning of the 2018/2019 academic session.'],
            ['type' => 'paragraph', 'text' => 'The school is located in a serene and quiet learning-friendly environment between Similoluwa and Ifeoluwa quarters of Olokun, Agbaluku Arigidi-Akoko. It was established based on a popular demand from parents, who wished for their children/wards who completed their primary school education at Emmaculate Nursery and Primary School to continue in a secondary school established by the same proprietor — so that they would be able to enjoy a continuation of the sound moral and academic foundation already laid in the primary school.'],
            ['type' => 'paragraph', 'text' => 'The school was opened with six teachers and fifteen pioneering students.'],
            ['type' => 'heading', 'text' => 'The teachers'],
            ['type' => 'list', 'items' => ['Mr. Oladeyo Isaac Seun', 'Mr. Akanbi Olatunde David', 'Mr. Mosunmola Ademola Oluwaseun', 'Miss Ayeni Oyindamola', 'Miss Seun Olaseni']],
            ['type' => 'heading', 'text' => 'The pioneering students (JSS 1)'],
            ['type' => 'list', 'items' => ['Agoi Ajoke', 'Bayode David', 'Adeniran Deborah', 'Abiola Michael', 'Ajibola Damilola', 'Bayode Toyosi David', 'Lucky Christianah', 'Olupona Tobi', 'Salami Bright', 'Marvellous Bunmi', 'Obaaro Olaiya', 'Ayeni Ezekiel', 'Akadri Faith', 'Uineh Chidinma Faith', 'Oseni Peace']],
            ['type' => 'quote', 'text' => 'May the Lord help to enlarge the college and take it to an enviable height, so that it will soon become a world-class secondary school, in Jesus’ name. Amen.'],
            ['type' => 'section', 'text' => 'A brief history of Emmaculate Day-care, Nursery and Primary School'],
            ['type' => 'list', 'items' => [
                'Emmaculate Day-care, Nursery and Primary School started formally on 18th September 2006 with a total enrolment of 141 pupils.',
                'The school was registered with the Ministry of Education, Akoko North-West Local Government office, Okeagbe, and later registered at the state level with the Ondo State Ministry of Education, Akure, on 16th October 2007.',
                'The school is located at No. 19 Agbaja Street, Olokun Quarters, Agbaluku Arigidi-Akoko, Ondo State.',
                'The first members of staff of the school were eight (8) in number, with Alekhue Emmanuel as the proprietor and Adebayo Emmanuel as the first Headmaster.',
                'When the school initially started, it passed through a turbulent time. However, by the grace of God, it is now among the fastest-growing schools in the locality, both in terms of staff strength and pupil enrolment. To God be the glory.',
            ]],
        ];
        $page->metadata = array_merge(is_array($page->metadata) ? $page->metadata : [], ['layout' => 'chapters']);
        $page->save();
    }
}
