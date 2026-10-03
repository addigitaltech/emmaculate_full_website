<?php

namespace Database\Seeders;

use App\Models\AcademicProgramme;
use App\Models\AdmissionsSettings;
use App\Models\CmsPage;
use App\Models\FooterSection;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\LeadershipProfile;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\PaymentGatewayConfig;
use App\Models\PortalLink;
use App\Models\SchoolSettings;
use Illuminate\Database\Seeder;

class PublicContentSeeder extends Seeder
{
    public function run(): void
    {
        $imageMeta = [
            'logo.jpg' => ['Emmaculate Academy school crest', 'Emmaculate Academy crest'],
            'school-gate.jpg' => ['Two students outside Emmaculate Academy school gate', 'School gate'],
            'students-group.jpg' => ['A group of Emmaculate Academy students', 'Students'],
            'students-reading.jpg' => ['Students reading together in front of the school building', 'Students reading'],
            'lab.jpg' => ['A student engaged in practical laboratory work', 'Laboratory work'],
            'textbooks.jpg' => ['Students holding their textbooks', 'Textbooks'],
            'principal.jpg' => ['Dr. E. O. Omoogun, Principal', 'Principal'],
            'proprietor.jpg' => ['Mr. A. O. Emmanuel, Proprietor', 'Proprietor'],
        ];
        $media = [];
        foreach ($imageMeta as $filename => [$alt, $caption]) {
            $path = 'migrated-images/'.$filename;
            $media[$filename] = MediaAsset::query()->firstOrCreate(
                ['path' => $path],
                [
                    'uploaded_by' => null,
                    'original_name' => $filename,
                    'path' => $path,
                    'disk' => 'public',
                    'mime_type' => 'image/jpeg',
                    'size_bytes' => (int) (\Illuminate\Support\Facades\Storage::disk('public')->size($path) ?: 0),
                    'alt_text' => $alt,
                    'caption' => $caption,
                ],
            );
        }

        SchoolSettings::query()->firstOrCreate(['id' => 1], [
            'school_name' => 'Emmaculate Academy',
            'short_name' => 'Emmaculate Academy',
            'motto' => 'Determined to make a difference',
            'description' => 'Emmaculate Academy is a school in Arigidi Akoko, Ondo State, raising a morally upright generation of future leaders through sound academic and moral foundations, from Primary to Secondary education.',
            'logo_path' => 'migrated-images/logo.jpg',
            'favicon_path' => 'migrated-images/logo.jpg',
            'address' => 'Arigidi Akoko, Ondo State, Nigeria',
            'phone_primary' => null,
            'phone_secondary' => null,
            'email' => null,
            'whatsapp' => null,
            'office_hours' => null,
            'social_links' => [],
            'theme_tokens' => [
                'brand_50' => '#FBEEEF', 'brand_100' => '#F3D6D9', 'brand_200' => '#E3A8AE',
                'brand_400' => '#B44A56', 'brand_600' => '#8C1D2C', 'brand_700' => '#731723',
                'brand_800' => '#5A121C', 'brand_900' => '#3D0C13', 'accent' => '#B98A2E',
                'ink' => '#1A1A1A', 'surface_alt' => '#FBF8F5', 'surface_dark' => '#23100F', 'border' => '#E7DFD8',
            ],
            'verified_stats' => [],
            'ca1_max_score' => 40,
            'ca2_max_score' => 0,
            'ca3_max_score' => 0,
            'exam_max_score' => 60,
            'pass_percentage' => 40,
            'highlight_fail_grade' => true,
            'payment_gate_results' => false,
            'result_access_mode' => 'portal_only',
        ]);

        $slides = [
            ['eyebrow' => 'Welcome to Emmaculate Academy', 'heading' => 'Determined to make a difference', 'body' => 'A citadel of learning in Arigidi Akoko, Ondo State — raising a morally upright generation of future leaders through sound academics and strong values.', 'image' => 'school-gate.jpg', 'alt' => 'Two Emmaculate Academy students standing outside the school gate', 'cta_label' => 'Discover Our School', 'cta_url' => '/about', 'secondary_cta_label' => 'Apply for Admission', 'secondary_cta_url' => '/admissions'],
            ['eyebrow' => 'Academics', 'heading' => 'Sound foundations, from Primary to Secondary', 'body' => 'From Emmaculate Nursery and Primary School through to Emmaculate Academy at Olokun, students enjoy a continuous moral and academic foundation.', 'image' => 'lab.jpg', 'alt' => 'A student engaged in practical laboratory work', 'cta_label' => 'Explore Academics', 'cta_url' => '/academics'],
            ['eyebrow' => 'Our Community', 'heading' => 'A citadel of learning, a choice of millions', 'body' => 'Guided by our anthem and pledge, we build character, integrity and academic excellence in every learner.', 'image' => 'students-reading.jpg', 'alt' => 'A group of students reading together in front of the school building', 'cta_label' => 'Read Our Mission & Vision', 'cta_url' => '/mission-vision'],
        ];
        foreach ($slides as $index => $slide) {
            HeroSlide::query()->firstOrCreate(['heading' => $slide['heading']], [
                'eyebrow' => $slide['eyebrow'], 'body' => $slide['body'], 'image_id' => $media[$slide['image']]->id,
                'image_path' => $media[$slide['image']]->path, 'image_alt' => $slide['alt'],
                'cta_label' => $slide['cta_label'], 'cta_url' => $slide['cta_url'],
                'secondary_cta_label' => $slide['secondary_cta_label'] ?? null,
                'secondary_cta_url' => $slide['secondary_cta_url'] ?? null,
                'is_enabled' => true, 'sort_order' => $index,
            ]);
        }

        $programmes = [
            ['level' => 'primary', 'title' => 'Primary School', 'slug' => 'primary', 'intro' => 'Emmaculate Day-care, Nursery and Primary School lays the foundation for lifelong learning, building strong moral and academic footing for every child before they progress into secondary education.', 'approach' => [], 'placeholder_note' => 'Detailed curriculum information for the Primary section has not yet been supplied. This section will be completed with official content from the school.', 'image_id' => $media['textbooks.jpg']->id, 'image_path' => $media['textbooks.jpg']->path],
            ['level' => 'secondary', 'title' => 'Secondary School', 'slug' => 'secondary', 'intro' => 'Emmaculate Academy continues the moral and academic foundation laid at the primary level, preparing students at Olokun, Agbaluku Arigidi, for secondary examinations and beyond.', 'approach' => [], 'placeholder_note' => 'Detailed curriculum information for the Secondary section has not yet been supplied. This section will be completed with official content from the school.', 'image_id' => $media['lab.jpg']->id, 'image_path' => $media['lab.jpg']->path],
        ];
        foreach ($programmes as $index => $programme) {
            $programme['is_published'] = true;
            $programme['sort_order'] = $index;
            AcademicProgramme::query()->firstOrCreate(['slug' => $programme['slug']], $programme);
        }

        AdmissionsSettings::query()->firstOrCreate([], [
            'status' => 'unconfirmed',
            'intro' => 'Emmaculate Academy welcomes enquiries from parents and guardians seeking a school where children continue to receive a sound moral and academic foundation, from Primary through Secondary education.',
            'eligibility' => null,
            'process_steps' => [],
            'requirements' => [],
            'important_dates' => [],
            'screening_information' => null,
            'cta_label' => 'Contact the School',
            'cta_url' => '/contact',
        ]);

        $leaders = [
            ['name' => 'Mr. A. O. Emmanuel', 'title' => 'Proprietor', 'photo' => 'proprietor.jpg'],
            ['name' => 'Dr. E. O. Omoogun', 'title' => 'Principal', 'photo' => 'principal.jpg'],
            ['name' => 'Mr. A. A. Ajayi', 'title' => 'Vice Principal', 'photo' => null],
            ['name' => 'Mr. T. M. Ayeniran', 'title' => 'Head Teacher', 'photo' => null],
            ['name' => 'Mrs. I. O. Emmanuel', 'title' => 'Bursar', 'photo' => null],
        ];
        foreach ($leaders as $index => $leader) {
            LeadershipProfile::query()->firstOrCreate(['name' => $leader['name']], [
                'title' => $leader['title'],
                'photo_id' => $leader['photo'] ? $media[$leader['photo']]->id : null,
                'photo_path' => $leader['photo'] ? $media[$leader['photo']]->path : null,
                'biography' => null,
                'qualifications' => null,
                'is_visible' => true,
                'sort_order' => $index + 1,
            ]);
        }

        $albums = [
            'campus' => 'Campus',
            'academics' => 'Academics',
            'school-life' => 'School Life',
        ];
        foreach ($albums as $slug => $title) {
            $album = GalleryAlbum::query()->firstOrCreate(['slug' => $slug], ['title' => $title, 'status' => 'published']);
            $images = match ($slug) {
                'campus' => [['school-gate.jpg', 'Two students outside the school gate']],
                'academics' => [['lab.jpg', 'A student engaged in practical laboratory work'], ['textbooks.jpg', 'Students holding their textbooks']],
                default => [['students-reading.jpg', 'Students reading together in front of the school building'], ['students-group.jpg', 'A group of Emmaculate Academy students']],
            };
            foreach ($images as $index => [$filename, $alt]) {
                GalleryImage::query()->firstOrCreate(
                    ['album_id' => $album->id, 'image_path' => $media[$filename]->path],
                    ['media_id' => $media[$filename]->id, 'caption' => $alt, 'alt_text' => $alt, 'sort_order' => $index, 'is_visible' => true],
                );
            }
        }

        $main = [
            ['Home', '/', null],
            ['About', '/about', null], ['About the Academy', '/about', 'About'], ['Our History', '/history', 'About'],
            ['Mission, Vision & Pledge', '/mission-vision', 'About'], ['Leadership', '/leadership', 'About'],
            ['Academics', '/academics', null], ['Academics Overview', '/academics', 'Academics'],
            ['Primary School', '/academics/primary', 'Academics'], ['Secondary School', '/academics/secondary', 'Academics'],
            ['Admissions', '/admissions', null], ['News & Events', '/news', null], ['News', '/news', 'News & Events'],
            ['Events', '/events', 'News & Events'], ['Announcements', '/announcements', 'News & Events'], ['Gallery', '/gallery', null], ['FAQ', '/faq', null], ['Contact', '/contact', null],
        ];
        $parents = [];
        foreach ($main as $index => [$label, $url, $parentLabel]) {
            $parentId = $parentLabel ? ($parents[$parentLabel] ?? null) : null;
            $item = NavigationItem::query()->firstOrCreate(
                ['menu' => 'main', 'label' => $label],
                ['parent_id' => $parentId, 'url' => $url, 'is_visible' => true, 'sort_order' => $index],
            );
            if (! $parentLabel) {
                $parents[$label] = $item->id;
            }
        }

        foreach ([
            ['Explore', [['label' => 'About the Academy', 'url' => '/about'], ['label' => 'Our History', 'url' => '/history'], ['label' => 'Leadership', 'url' => '/leadership'], ['label' => 'Academics', 'url' => '/academics'], ['label' => 'Gallery', 'url' => '/gallery']]],
            ['Admissions', [['label' => 'Admissions Overview', 'url' => '/admissions'], ['label' => 'FAQ', 'url' => '/faq'], ['label' => 'News & Events', 'url' => '/news'], ['label' => 'Announcements', 'url' => '/announcements'], ['label' => 'Contact Us', 'url' => '/contact']]],
            ['School Portal', [['label' => 'All Portals', 'url' => '/portal']]],
        ] as $index => [$title, $links]) {
            FooterSection::query()->firstOrCreate(['title' => $title], ['links' => $links, 'is_visible' => true, 'sort_order' => $index]);
        }

        foreach ([
            ['key' => 'students', 'title' => 'Students', 'description' => 'Your own academic records and published results.', 'url' => '/login', 'sort_order' => 1],
            ['key' => 'parents', 'title' => 'Parents', 'description' => 'Linked children, published results and permitted fee records.', 'url' => '/login', 'sort_order' => 2],
            ['key' => 'teachers', 'title' => 'Teachers', 'description' => 'Assigned classes, subjects and score-entry tools.', 'url' => '/login', 'sort_order' => 3],
            ['key' => 'school-staff', 'title' => 'School staff', 'description' => 'CMS, results and finance tools according to your role.', 'url' => '/login', 'sort_order' => 4],
        ] as $portalLink) {
            PortalLink::query()->firstOrCreate(['key' => $portalLink['key']], $portalLink + ['status' => 'live']);
        }

        foreach ([
            ['driver' => 'paystack', 'display_name' => 'Paystack', 'supports_online_checkout' => true, 'supports_refunds' => true, 'configuration_status' => 'not_configured'],
            ['driver' => 'flutterwave', 'display_name' => 'Flutterwave', 'supports_online_checkout' => true, 'supports_refunds' => true, 'configuration_status' => 'not_configured'],
            ['driver' => 'moniepoint', 'display_name' => 'Moniepoint POS', 'supports_online_checkout' => false, 'supports_refunds' => false, 'configuration_status' => 'product_confirmation_required'],
        ] as $config) {
            $gateway = PaymentGatewayConfig::query()->firstOrNew(['driver' => $config['driver']]);
            if (! $gateway->exists) $gateway->is_enabled = false;
            $gateway->fill($config)->save();
        }

        $pages = [
            'about' => [
                'title' => 'About Emmaculate Academy',
                'eyebrow' => 'About Us',
                'excerpt' => 'A continuous moral and academic foundation from early years through secondary education.',
                'content_blocks' => [
                    ['type' => 'paragraph', 'text' => 'Emmaculate Academy, together with Emmaculate Day-care, Nursery and Primary School, forms the Emmaculate Group of Schools in Arigidi Akoko, Ondo State — carrying students from their earliest years through to secondary education under one continuous moral and academic foundation.'],
                    ['type' => 'paragraph', 'text' => 'Guided by our vision of raising a morally upright generation of future leaders, we are determined to make a difference in every child we teach.'],
                ],
            ],
            'history' => [
                'title' => 'Our History',
                'eyebrow' => 'Our Story',
                'excerpt' => 'The histories of Emmaculate Day-care, Nursery and Primary School and Emmaculate Academy.',
                'content_blocks' => [
                    ['type' => 'heading', 'text' => 'Emmaculate Academy, Olokun, Agbaluku Arigidi'],
                    ['type' => 'paragraph', 'text' => 'Emmaculate Academy, the secondary school arm of Emmaculate Group of Schools, was established on 4th September 2018, at the beginning of the 2018/2019 academic session. The school is located in a serene and quiet learning-friendly environment between Similoluwa and Ifeoluwa quarters of Olokun, Agbaluku Arigidi-Akoko.'],
                    ['type' => 'paragraph', 'text' => 'It was established based on a popular demand from parents, who wished for their children/wards who completed their primary school education at Emmaculate Nursery and Primary School to continue in a secondary school established by the same proprietor — so that they would be able to enjoy a continuation of the sound moral and academic foundation already laid in the primary school.'],
                    ['type' => 'paragraph', 'text' => 'The school was opened with six teachers and fifteen pioneering students.'],
                    ['type' => 'heading', 'text' => 'Pioneering teachers'],
                    ['type' => 'list', 'items' => ['Mr. Oladeyo Isaac Seun', 'Mr. Akanbi Olatunde David', 'Mr. Mosunmola Ademola Oluwaseun', 'Miss Ayeni Oyindamola', 'Miss Seun Olaseni']],
                    ['type' => 'paragraph', 'text' => 'A sixth pioneering teacher is recorded in the school register; name to be added once confirmed.'],
                    ['type' => 'heading', 'text' => 'Pioneering students'],
                    ['type' => 'list', 'items' => ['Agoi Ajoke', 'Bayode David', 'Adeniran Deborah', 'Abiola Michael', 'Ajibola Damilola', 'Bayode Toyosi David', 'Lucky Christianah', 'Olupona Tobi', 'Salami Bright', 'Marvellous Bunmi', 'Obaaro Olaiya', 'Ayeni Ezekiel', 'Akadri Faith', 'Uineh Chidinma Faith', 'Oseni Peace']],
                    ['type' => 'paragraph', 'text' => 'May the Lord help to enlarge the college and take it to an enviable height, so that it will soon become a world-class secondary school, in Jesus’ name. Amen.'],
                    ['type' => 'heading', 'text' => 'Emmaculate Day-care, Nursery and Primary School'],
                    ['type' => 'paragraph', 'text' => 'Emmaculate Day-care, Nursery and Primary School started formally on 18th September 2006 with a total enrolment of 141 pupils.'],
                    ['type' => 'paragraph', 'text' => 'The school was registered with the Ministry of Education, Akoko North-West Local Government office, Okeagbe, and later registered at the state level with the Ondo State Ministry of Education, Akure, on 16th October 2007.'],
                    ['type' => 'paragraph', 'text' => 'The school is located at No. 19 Agbaja Street, Olokun Quarters, Agbaluku Arigidi-Akoko, Ondo State.'],
                    ['type' => 'paragraph', 'text' => 'The first members of staff of the school were eight (8) in number, with Alekhue Emmanuel as the proprietor and Adebayo Emmanuel as the first Headmaster.'],
                    ['type' => 'paragraph', 'text' => 'When the school initially started, it passed through a turbulent time. However, by the grace of God, it is now among the fastest-growing schools in the locality, both in terms of staff strength and pupil enrolment. To God be the glory.'],
                ],
            ],
            'mission-vision' => [
                'title' => 'Mission, Vision, Pledge & Anthem',
                'eyebrow' => 'What Guides Us',
                'excerpt' => 'The official words that guide the Emmaculate Academy community.',
                'content_blocks' => [
                    ['type' => 'heading', 'text' => 'Our Vision'],
                    ['type' => 'paragraph', 'text' => 'Becoming and maintaining the position of a world class educational service provider for the overall intent of raising a morally upright generation of future leaders; thereby making a difference.'],
                    ['type' => 'heading', 'text' => 'Our Mission'],
                    ['type' => 'paragraph', 'text' => 'To provide well-educated future leaders of unquestionable integrity and diligence who will make Nigeria and the entire world a better place for humanity.'],
                    ['type' => 'heading', 'text' => 'Our Pledge'],
                    ['type' => 'list', 'items' => ['To Emmaculate School I pledge allegiance.', 'To be the virtue of honesty, integrity and faithfulness at all times.', 'To uphold moral and academic standard.', 'To stay with tested principles of success.', 'Co-operative, friendly, accommodating and be willing to be of service to others.', 'So help me God.']],
                    ['type' => 'heading', 'text' => 'Our Anthem'],
                    ['type' => 'list', 'items' => ['Emmaculate school,', 'A citadel of learning,', 'A choice of millions,', 'A focus on total quality,', 'Aim and providing,', 'Academic excellence,', 'To build a future for our children,', 'To usher in culture,', 'Transparency, honesty and purity.']],
                ],
            ],
        ];

        foreach ($pages as $slug => $page) {
            CmsPage::query()->firstOrCreate(['slug' => $slug], [
                'title' => $page['title'],
                'eyebrow' => $page['eyebrow'],
                'excerpt' => $page['excerpt'],
                'content' => null,
                'content_blocks' => $page['content_blocks'],
                'status' => 'published',
                'published_at' => now(),
            ]);
        }
    }
}
