<x-filament-widgets::widget>
    <x-filament::section heading="Start here" description="The jobs people do most. Everything else is in the menu on the left.">
        @php($actions = $this->getActions())
        @if(count($actions) === 0)
            <p class="text-sm text-gray-500">Use the menu on the left to open the pages you have access to.</p>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:.75rem">
                @foreach($actions as $action)
                    <a href="{{ $action['url'] }}" style="display:flex;gap:.75rem;align-items:center;padding:.9rem 1rem;border:1px solid rgba(120,120,120,.25);border-radius:.75rem;text-decoration:none">
                        <x-filament::icon :icon="$action['icon']" style="width:1.6rem;height:1.6rem;flex:none" />
                        <span style="display:block"><strong style="display:block">{{ $action['label'] }}</strong><span style="font-size:.8rem;opacity:.7">{{ $action['hint'] }}</span></span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
