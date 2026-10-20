<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            {{ $this->promotionRulesSummary() }}
        </div>

        <form wire:submit="launch" class="space-y-4">
            {{ $this->form }}
            <x-filament::button type="submit" icon="heroicon-o-play">
                {{ $this->promotionMode() === 'automatic' ? 'Run automatic promotions' : 'Prepare manual review list' }}
            </x-filament::button>
        </form>

        @php($promotions = $this->records())
        @php($classes = $this->activeClasses())

        @if ($promotions->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Historic class / arm</th>
                            <th class="px-4 py-3">Cumulative average</th>
                            <th class="px-4 py-3">Subjects passed</th>
                            <th class="px-4 py-3">Eligibility</th>
                            <th class="px-4 py-3">Status / review reason</th>
                            <th class="px-4 py-3">Decision</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($promotions as $promotion)
                            @php($targetClassId = $manualTargets[$promotion->id]['class_id'] ?? $promotion->destination_class_id)
                            @php($targetArmId = $manualTargets[$promotion->id]['arm_id'] ?? $promotion->destination_arm_id)
                            @php($targetClass = $classes->first(fn ($class) => (int) $class->id === (int) $targetClassId))
                            <tr wire:key="promotion-{{ $promotion->id }}" class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">
                                    {{ $promotion->student?->fullName() ?? 'Student record unavailable' }}
                                    <div class="text-xs font-normal text-gray-500">{{ $promotion->student?->student_number }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ $promotion->sourceClass?->name ?? 'Class unavailable' }}
                                    @if ($promotion->sourceArm)
                                        / {{ $promotion->sourceArm->name }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $promotion->cumulative_average === null ? '—' : number_format((float) $promotion->cumulative_average, 2).'%' }}</td>
                                <td class="px-4 py-3">{{ $promotion->subjects_passed }} / {{ $promotion->subjects_offered }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs {{ $promotion->eligible ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' }}">
                                        {{ $promotion->eligible ? 'Meets rules' : 'Review' }}
                                    </span>
                                </td>
                                <td class="max-w-sm px-4 py-3">
                                    <span class="font-medium capitalize">{{ $promotion->decision_status }}</span>
                                    @if ($promotion->review_reason)
                                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $promotion->review_reason }}</div>
                                    @endif
                                </td>
                                <td class="min-w-64 px-4 py-3">
                                    @if ($promotion->decision_status === 'review')
                                        <div class="space-y-2">
                                            <select wire:model.live="manualTargets.{{ $promotion->id }}.class_id" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                                                <option value="">Choose destination class</option>
                                                @foreach ($classes as $class)
                                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                                @endforeach
                                            </select>
                                            <select wire:model="manualTargets.{{ $promotion->id }}.arm_id" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                                                <option value="">No arm</option>
                                                @foreach (($targetClass?->arms ?? collect()) as $arm)
                                                    <option value="{{ $arm->id }}">{{ $arm->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="flex gap-2">
                                                <x-filament::button size="sm" color="success" wire:click="decide({{ $promotion->id }}, 'promote')">Promote</x-filament::button>
                                                <x-filament::button size="sm" color="gray" wire:click="decide({{ $promotion->id }}, 'retain')">Retain</x-filament::button>
                                                @if($promotion->sourceClass?->is_graduating)
                                                    <x-filament::button size="sm" color="warning" wire:click="decide({{ $promotion->id }}, 'graduate')" wire:confirm="Graduate this student? They move to the archive and can no longer sign in or check results.">Graduate</x-filament::button>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">Decision recorded</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif (($this->data['source_session_id'] ?? null) && ($this->data['destination_session_id'] ?? null))
            <div class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
                No promotion decisions are recorded for this transition yet. Launch the batch after checking that the source session's third-term results are published.
            </div>
        @endif
    </div>
</x-filament-panels::page>
