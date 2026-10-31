<x-filament-panels::page>
    @php($checks = $this->checks())
    @php($failed = collect($checks)->where('ok', false)->count())
    <x-filament::section :heading="$failed === 0 ? 'Everything checked is in good shape' : $failed.' thing(s) need attention'" description="These checks look only at settings. They do not replace backups, strong passwords and careful sharing of logins.">
        <ul style="list-style:none;padding:0;margin:0">
            @foreach($checks as $check)
                <li style="display:flex;gap:.75rem;padding:.6rem 0;border-bottom:1px solid rgba(120,120,120,.2)">
                    <span style="font-size:1.2rem;line-height:1.2">{{ $check['ok'] ? '✅' : '⚠️' }}</span>
                    <span><strong>{{ $check['label'] }}</strong>@if(! $check['ok'] && $check['fix'])<span style="display:block;font-size:.85rem;opacity:.8">{{ $check['fix'] }}</span>@endif</span>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-panels::page>
