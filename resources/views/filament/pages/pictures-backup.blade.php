<x-filament-panels::page>
    <x-filament::section heading="What this is for" description="Pictures you upload live in the website's files, not in the database. Use this page to be sure none are missing, and to move them between hosts.">
        <ul style="list-style:disc;padding-left:1.2rem;line-height:1.7">
            <li><strong>Check pictures</strong> lists any picture the website points to that is not on this server (for example after moving to a new host).</li>
            <li><strong>Download all pictures</strong> packs every picture, with a list of where each is used, into one ZIP. Do this on the old site.</li>
            <li><strong>Load a ZIP</strong> (below) puts pictures from such a ZIP into this site. Existing pictures are never overwritten, and anything that is not a safe picture is refused.</li>
            <li>To keep the pictures in your project files too, unzip the download and copy its folders into <code>storage/app/public/</code>.</li>
        </ul>
    </x-filament::section>

    @if($checkedTotal !== null)
        <x-filament::section :heading="count($missing) === 0 ? 'All '.$checkedTotal.' pictures are in place' : count($missing).' missing picture(s)'">
            @if(count($missing))
                <ul style="list-style:disc;padding-left:1.2rem;font-family:monospace;font-size:.85rem">@foreach($missing as $line)<li>{{ $line }}</li>@endforeach</ul>
                <p style="margin-top:.6rem;font-size:.85rem;opacity:.8">Load the ZIP from the old site below, or upload these pictures again where they are used.</p>
            @endif
        </x-filament::section>
    @endif

    <x-filament::section heading="Load pictures from a ZIP">
        <form wire:submit.prevent="restore">
            {{ $this->form }}
            <div style="margin-top:1rem"><x-filament::button type="submit">Load pictures</x-filament::button></div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
