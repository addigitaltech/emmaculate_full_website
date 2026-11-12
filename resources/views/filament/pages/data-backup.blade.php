<x-filament-panels::page>
    <x-filament::section heading="What this backup contains" description="Use these ZIP files when moving to a new domain or recovering from accidental deletion.">
        <ul style="list-style:disc;padding-left:1.2rem;line-height:1.7">
            <li><strong>Results ZIP:</strong> all result rows, or one selected academic term. Students are matched by Student ID and subjects by subject code/name. Existing records are updated; nothing is deleted.</li>
            <li><strong>People ZIP:</strong> user login identifiers, roles, student/teacher/parent biodata and links. Password hashes, PINs, reset tokens and secrets are never exported.</li>
            <li>For a new site, create the user accounts and assign roles first, then import the People ZIP. Existing accounts are matched by email.</li>
            <li>Keep these ZIP files private. They contain student results and personal data.</li>
        </ul>
    </x-filament::section>

    <x-filament::section heading="Restore a backup">
        <form wire:submit.prevent="restore">
            {{ $this->form }}
            <div style="margin-top:1rem"><x-filament::button type="submit">Restore selected ZIP files</x-filament::button></div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
