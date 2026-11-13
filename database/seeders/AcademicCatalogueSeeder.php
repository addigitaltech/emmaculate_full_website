<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Arm;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectCategory;
use App\Models\WaecSubjectCode;
use Illuminate\Database\Seeder;

class AcademicCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = collect(['2026/2027', '2027/2028', '2028/2029', '2029/2030'])->mapWithKeys(function (string $name): array {
            $session = AcademicSession::query()->firstOrCreate(['name' => $name], ['is_active' => false]);
            foreach ([1 => 'First Term', 2 => 'Second Term', 3 => 'Third Term'] as $sequence => $termName) {
                $session->terms()->firstOrCreate(['sequence' => $sequence], ['name' => $termName, 'is_current' => false]);
            }
            return [$name => $session];
        });

        $classes = collect(['JSS 1', 'JSS 2', 'JSS 3', 'SS 1', 'SS 2', 'SS 3'])->mapWithKeys(function (string $name): array {
            $level = str_starts_with($name, 'JSS') ? 'Junior Secondary' : 'Senior Secondary';
            return [$name => SchoolClass::query()->firstOrCreate(['name' => $name], ['level' => $level, 'is_active' => false])];
        });

        foreach (['JSS 1A' => 'JSS 1', 'JSS 1B' => 'JSS 1', 'JSS 1C' => 'JSS 1', 'JSS 2A' => 'JSS 2', 'JSS 2B' => 'JSS 2', 'JSS 2C' => 'JSS 2', 'JSS 3A' => 'JSS 3', 'JSS 3B' => 'JSS 3', 'JSS 3C' => 'JSS 3', 'Science' => 'SS 1', 'Commercial' => 'SS 1', 'Humanities/Arts' => 'SS 1'] as $armName => $className) {
            $arm = Arm::query()->firstOrCreate(['name' => $armName], ['is_active' => false]);
            $targetClasses = str_starts_with($className, 'SS') && in_array($armName, ['Science', 'Commercial', 'Humanities/Arts'], true)
                ? $classes->only(['SS 1', 'SS 2', 'SS 3'])
                : $classes->only([$className]);
            $arm->classes()->syncWithoutDetaching($targetClasses->pluck('id')->all());
        }

        $categories = collect([
            'JSS Core' => 'JSS-CORE',
            'Senior Core' => 'SS-CORE',
            'Science' => 'SS-SCIENCE',
            'Commercial' => 'SS-COMMERCIAL',
            'Humanities and Arts' => 'SS-HUMANITIES',
            'Trade and Vocational' => 'SS-TRADE',
        ])->mapWithKeys(fn (string $code, string $name): array => [$name => SubjectCategory::query()->firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => false])]);

        $jss = [
            'English Studies' => 'JSS-ENG', 'Mathematics' => 'JSS-MTH', 'Basic Science' => 'JSS-BSC', 'Basic Technology' => 'JSS-BTC',
            'Digital Technologies' => 'JSS-DGT', 'Business Studies' => 'JSS-BST', 'Social and Citizenship Studies' => 'JSS-SCS', 'Nigerian History' => 'JSS-NHS',
            'Cultural and Creative Arts' => 'JSS-CCA', 'Physical and Health Education' => 'JSS-PHE', 'Agricultural Science' => 'JSS-AGR',
            'Christian Religious Studies' => 'JSS-CRS', 'Islamic Studies' => 'JSS-IRS', 'French' => 'JSS-FRE', 'Arabic' => 'JSS-ARA',
            'Yoruba' => 'JSS-YOR', 'Hausa' => 'JSS-HAU', 'Igbo' => 'JSS-IGB', 'Entrepreneurship' => 'JSS-ENT', 'Home Economics' => 'JSS-HEC',
            'Security Education' => 'JSS-SEC', 'General Studies' => 'JSS-GST',
        ];
        foreach ($jss as $name => $code) {
            $this->subject($name, $code, $categories['JSS Core'], $classes->only(['JSS 1', 'JSS 2', 'JSS 3']));
        }

        $seniorGroups = [
            'Senior Core' => ['English Language' => 'SS-ENG', 'General Mathematics' => 'SS-MTH', 'Citizenship and Heritage Studies' => 'SS-CHS', 'Digital Technologies' => 'SS-DGT', 'Trade Subject' => 'SS-TRD'],
            'Science' => ['Biology' => 'SS-BIO', 'Chemistry' => 'SS-CHM', 'Physics' => 'SS-PHY', 'Agricultural Science' => 'SS-AGR', 'Further Mathematics' => 'SS-FMT', 'Physical Education' => 'SS-PED', 'Health Education' => 'SS-HED', 'Foods and Nutrition' => 'SS-FDN', 'Geography' => 'SS-GEO', 'Technical Drawing' => 'SS-TDR'],
            'Commercial' => ['Accounting' => 'SS-ACC', 'Commerce' => 'SS-COM', 'Marketing' => 'SS-MKT', 'Economics' => 'SS-ECO'],
            'Humanities and Arts' => ['Nigerian History' => 'SS-NHS', 'Government' => 'SS-GOV', 'Christian Religious Studies' => 'SS-CRS', 'Islamic Studies' => 'SS-IRS', 'Nigerian Languages' => 'SS-NLG', 'French' => 'SS-FRE', 'Arabic' => 'SS-ARA', 'Visual Arts' => 'SS-VAR', 'Music' => 'SS-MUS', 'Literature in English' => 'SS-LIT', 'Home Management' => 'SS-HMG', 'Catering Craft' => 'SS-CCR'],
            'Trade and Vocational' => ['Solar Photovoltaic Installation and Maintenance' => 'SS-SPV', 'Fashion Design and Garment Making' => 'SS-FDG', 'Livestock Farming' => 'SS-LSF', 'Beauty and Cosmetology' => 'SS-BCT', 'Computer Hardware and GSM Repairs' => 'SS-CHR', 'Horticulture and Crop Production' => 'SS-HCP'],
        ];
        foreach ($seniorGroups as $category => $subjects) {
            foreach ($subjects as $name => $code) {
                $this->subject($name, $code, $categories[$category], $classes->only(['SS 1', 'SS 2', 'SS 3']));
            }
        }

        $waec = [
            'Commerce' => '103', 'Accounting' => '104', 'Marketing' => '112', 'Christian Religious Studies' => '202', 'Economics' => '203',
            'Geography' => '204', 'Government' => '205', 'Nigerian History' => '207', 'Islamic Studies' => '208', 'Literature in English' => '210',
            'Civic Education' => '216', 'Arabic' => '301', 'English Language' => '302', 'French' => '304', 'Hausa' => '327', 'Igbo' => '328',
            'Yoruba' => '329', 'Further Mathematics' => '401', 'General Mathematics' => '402', 'Agriculture' => '502', 'Biology' => '504',
            'Chemistry' => '505', 'Health Education' => '508', 'Physical Education' => '511', 'Physics' => '512', 'Technical Drawing' => '608',
            'Foods and Nutrition' => '702', 'Home Management' => '703', 'Music' => '705', 'Visual Art' => '706',
        ];
        foreach ($waec as $name => $code) {
            $waecCode = WaecSubjectCode::query()->firstOrCreate(['code' => $code], ['subject_name' => $name, 'is_active' => false]);
            Subject::query()->where('name', $name)->whereNull('waec_subject_code_id')->update(['waec_subject_code_id' => $waecCode->id]);
        }
    }

    private function subject(string $name, string $code, SubjectCategory $category, $classes): void
    {
        $waec = WaecSubjectCode::query()->where('subject_name', $name)->first();
        $subject = Subject::query()->firstOrCreate(['code' => $code], [
            'name' => $name,
            'subject_category_id' => $category->id,
            'waec_subject_code_id' => $waec?->id,
            'status' => 'inactive',
        ]);
        $subject->schoolClasses()->syncWithoutDetaching($classes->pluck('id')->all());
    }
}
