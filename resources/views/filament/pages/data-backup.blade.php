<x-filament-panels::page>
    <x-filament::section heading="What this backup contains" description="Use these ZIP files when moving to a new domain or recovering from accidental deletion.">
        <ul style="list-style:disc;padding-left:1.2rem;line-height:1.7">
            <li><strong>Results ZIP:</strong> all result rows, or one selected academic term. Students are matched by Student ID and subjects by subject code/name. Existing records are updated; nothing is deleted.</li>
            <li><strong>People ZIP:</strong> user login identifiers, roles, student/teacher/parent biodata and links. When a passphrase is supplied below, password hashes are included in an encrypted credentials file so restored users can keep their existing passwords. Plain-text passwords, PINs, reset tokens and secrets are never exported.</li>
            <li>Use the same passphrase when restoring on the new domain. Keep it separate from the ZIP and never send it by email.</li>
            <li>Keep these ZIP files private. They contain student results and personal data.</li>
        </ul>
    </x-filament::section>

    <x-filament::section heading="Restore a backup">
        <p style="margin:0 0 1rem">Use the buttons above to download or restore bulk Results ZIPs and to move login accounts with their existing passwords. Results backups do not require a passphrase; encrypted People backups do. A People passphrase is never included in the ZIP.</p>
        <form wire:submit.prevent="restore">
            {{ $this->form }}
            <div style="margin-top:1rem"><x-filament::button type="submit">Restore selected ZIP files</x-filament::button></div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
