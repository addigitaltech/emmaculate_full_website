@extends('site.layout')
@section('title', 'Portal Dashboard | '.$settings->short_name)
@section('content')
<section class="portal-head">
    <div class="site-container portal-head__inner">
        <div>
            <h1>Welcome, {{ $user->name }}</h1>
            <p>{{ ['student' => 'Student portal', 'parent' => 'Parent portal', 'teacher' => 'Staff portal', 'admin' => 'Administration', 'account' => 'School portal'][$kind] ?? 'School portal' }} &middot; Account: {{ ucfirst($kind) }}</p>
        </div>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap">
            @if($kind === 'admin' && ($user->can('manage website content') || $user->can('manage results') || $user->can('manage payments') || $user->can('manage students')))<a class="btn btn--ghost-light" href="{{ url('/admin') }}">Admin console</a>@endif
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="btn btn--ghost-light">Sign out</button></form>
        </div>
    </div>
</section>
@if(session('success'))<div class="site-container" style="margin-top:1rem"><div role="status" class="alert alert--ok">{{ session('success') }}</div></div>@endif
<section class="portal-body"><div class="site-container">
@if($kind === 'student')
    @if($profile)
    <div class="panel"><div class="panel__body student-chip">
        @if($profile->photo)<img src="{{ $profile->photo->url() }}" alt="" width="64" height="64">@else<span class="student-chip__ph">{{ strtoupper(substr($profile->first_name, 0, 1)) }}</span>@endif
        <div><strong style="font-size:1.15rem">{{ $profile->fullName() }}</strong><div class="muted" style="font-size:.9rem">{{ $profile->student_number }} &middot; {{ $profile->schoolClass?->name ?? 'Class not assigned' }}@if($profile->arm) ({{ $profile->arm->name }})@endif</div></div>
    </div></div>
    @endif
    <div class="panel">
        <div class="panel__head"><h2>My report cards</h2></div>
        <div class="panel__body">
            @if($reportTerms->isEmpty())
                <p class="empty-state">No results have been published for you yet. They will appear here once the school publishes them.</p>
            @else
                <ul class="report-list">@foreach($reportTerms as $reportTerm)<li><a href="{{ route('reports.show', ['student' => $profile->id, 'term' => $reportTerm->id]) }}" target="_blank" rel="noopener"><span>{{ $reportTerm->session?->name }} &middot; {{ $reportTerm->name }}</span><span class="link-action link-action--blue" style="margin:0">View / print</span></a></li>@endforeach</ul>
            @endif
        </div>
    </div>
@elseif($kind === 'parent')
    @if($children->isEmpty())
        <p class="empty-state">No student profiles are linked to this parent account. Please contact the school office.</p>
    @else
        @foreach($children as $child)
            <div class="panel">
                <div class="panel__head"><div class="student-chip">@if($child->photo)<img src="{{ $child->photo->url() }}" alt="" width="64" height="64">@else<span class="student-chip__ph">{{ strtoupper(substr($child->first_name, 0, 1)) }}</span>@endif<div><h2>{{ $child->fullName() }}</h2><span class="muted" style="font-size:.88rem">{{ $child->student_number }} &middot; {{ $child->schoolClass?->name ?? 'Class not assigned' }}@if($child->arm) ({{ $child->arm->name }})@endif</span></div></div></div>
                <div class="panel__body">
                    @php($terms = $childTerms[$child->id] ?? collect())
                    @if($terms->isEmpty())
                        <p class="muted" style="margin:0">No published results yet.</p>
                    @else
                        <ul class="report-list">@foreach($terms as $reportTerm)<li><a href="{{ route('reports.show', ['student' => $child->id, 'term' => $reportTerm->id]) }}" target="_blank" rel="noopener"><span>{{ $reportTerm->session?->name }} &middot; {{ $reportTerm->name }}</span><span class="link-action link-action--blue" style="margin:0">View / print</span></a></li>@endforeach</ul>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
@elseif($kind === 'teacher')
    <div class="panel">
        <div class="panel__head"><h2>My classes and subjects</h2><a class="btn btn--maroon btn--sm" href="{{ route('staff.results') }}">Manage results</a></div>
        <div class="panel__body">
            @if($assignments->isEmpty())
                <p class="empty-state">No class or subject has been assigned to this account yet. Please contact the school office.</p>
            @else
                <div class="class-grid">
                    @foreach($assignments as $assignment)
                        <a class="panel class-card" style="text-decoration:none;color:inherit;margin:0" href="{{ route('staff.results.subject', $assignment) }}">
                            <h3>{{ $assignment->subject?->name }}</h3>
                            <span class="muted">{{ $assignment->schoolClass?->name }}@if($assignment->arm) &middot; {{ $assignment->arm->name }}@endif</span>
                            <span class="link-action link-action--green" style="margin:.7rem 0 0">Open score sheet &rarr;</span>
                        </a>
                    @endforeach
                </div>
            @endif
            <p style="margin:1.2rem 0 0"><a class="link-more" href="{{ route('staff.results.archive') }}">Reports archive &rarr;</a></p>
        </div>
    </div>
@else
    <div class="kpis">
        @if($user->can('publish results'))<div class="kpi"><span>Results awaiting publication</span><strong>{{ $pendingTotal }}</strong></div>@endif
    </div>
    <div class="panel" style="margin-top:1.2rem">
        <div class="panel__head"><h2>Administration</h2></div>
        <div class="panel__body">
            <p class="muted" style="margin-top:0">Tools shown here depend on the permissions granted to your account.</p>
            <div style="display:flex;flex-wrap:wrap;gap:.7rem">
                @if($user->can('manage website content') || $user->can('manage results') || $user->can('manage payments') || $user->can('manage students'))<a class="btn btn--maroon" href="{{ url('/admin') }}">Open admin console</a>@endif
                @if($user->can('manage results') || $user->can('enter assigned results'))<a class="btn btn--navy" href="{{ route('staff.results') }}">Manage results</a><a class="btn btn--outline" href="{{ route('staff.results.archive') }}">Reports archive</a>@endif
            </div>
        </div>
    </div>
    @if($activity->isNotEmpty())
    <div class="panel"><div class="panel__head"><h2>Recent activity</h2></div><div class="panel__body"><ul style="margin:0;padding:0;list-style:none">@foreach($activity as $event)<li style="padding:.45rem 0;border-bottom:1px solid var(--line);font-size:.9rem"><strong>{{ $event->event }}</strong> &middot; {{ $event->created_at?->format('M j, Y H:i') }}</li>@endforeach</ul></div></div>
    @endif

@endif

@if(in_array($kind, ['student', 'parent'], true))
    <section class="mt-10"><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">School fees</h2>
    @if($fees->isEmpty())<p class="mt-4 rounded-lg border border-dashed border-[var(--border)] p-5 text-sm text-[var(--ink-soft)]">There are no unpaid fee assignments for this account.</p>
    @else<div class="mt-4 overflow-x-auto rounded-lg border border-[var(--border)]"><table class="w-full min-w-[760px] text-left text-sm"><thead class="bg-[var(--surface-alt)]"><tr>@if($kind === 'parent')<th class="p-3">Student</th>@endif<th class="p-3">Fee</th><th class="p-3">Amount</th><th class="p-3">Due date</th><th class="p-3">Payment options</th></tr></thead><tbody>@foreach($fees as $fee)<tr class="border-t border-[var(--border)]">@if($kind === 'parent')<td class="p-3">{{ $fee->student?->fullName() }}</td>@endif<td class="p-3">{{ $fee->feeType?->name ?? 'School fee' }}@if($fee->description)<span class="block text-xs text-[var(--ink-soft)]">{{ $fee->description }}</span>@endif</td><td class="p-3">{{ $fee->currency }} {{ number_format((float)$fee->amount_due, 2) }}</td><td class="p-3">{{ $fee->due_date?->format('M j, Y') ?? '—' }}</td><td class="p-3">@forelse($gatewayOptions as $gateway)<form method="post" action="{{ route('payments.checkout', ['assignment' => $fee->id, 'gateway' => $gateway['driver']]) }}" class="inline-block mr-2" data-confirm="Continue to the secure {{ $gateway['name'] }} checkout to pay this school fee?">@csrf<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><button class="mb-1 rounded-md bg-[var(--brand-700)] px-3 py-2 text-xs font-semibold text-white">Pay with {{ $gateway['name'] }}</button></form>@empty<span class="text-xs text-[var(--ink-soft)]">Online checkout is not configured by the school. Contact the office for payment instructions.</span>@endforelse</td></tr>@endforeach</tbody></table></div>@endif
    @if($payments->isNotEmpty())<h3 class="mt-7 font-display text-xl font-semibold text-[var(--brand-800)]">Recent payment activity</h3><div class="mt-3 overflow-x-auto rounded-lg border border-[var(--border)]"><table class="w-full min-w-[650px] text-left text-sm"><thead class="bg-[var(--surface-alt)]"><tr>@if($kind === 'parent')<th class="p-3">Student</th>@endif<th class="p-3">Amount</th><th class="p-3">Method</th><th class="p-3">Status</th><th class="p-3">Receipt</th><th class="p-3">Date</th></tr></thead><tbody>@foreach($payments as $payment)<tr class="border-t border-[var(--border)]">@if($kind === 'parent')<td class="p-3">{{ $payment->student?->fullName() }}</td>@endif<td class="p-3">{{ $payment->currency }} {{ number_format((float)$payment->amount,2) }}</td><td class="p-3">{{ ucfirst($payment->gateway) }}</td><td class="p-3">{{ $payment->status === 'successful' ? 'Verified' : ($payment->status === 'pending' ? 'Awaiting verification' : ucfirst($payment->status)) }}</td><td class="p-3">{{ $payment->receipt?->receipt_number ?? '—' }}</td><td class="p-3">{{ $payment->created_at?->format('M j, Y') }}</td></tr>@endforeach</tbody></table></div>@endif
    </section>
@elseif($kind === 'admin' && $user->can('manage payments') && $payments->isNotEmpty())
    <section class="mt-10"><h2 class="font-display text-2xl font-semibold text-[var(--brand-800)]">Recent payment transactions</h2><div class="mt-4 overflow-x-auto rounded-lg border border-[var(--border)]"><table class="w-full min-w-[700px] text-left text-sm"><thead class="bg-[var(--surface-alt)]"><tr><th class="p-3">Student</th><th class="p-3">Amount</th><th class="p-3">Method</th><th class="p-3">Status</th><th class="p-3">Receipt</th></tr></thead><tbody>@foreach($payments as $payment)<tr class="border-t border-[var(--border)]"><td class="p-3">{{ $payment->student?->fullName() }}</td><td class="p-3">{{ $payment->currency }} {{ number_format((float)$payment->amount,2) }}</td><td class="p-3">{{ ucfirst($payment->gateway) }}</td><td class="p-3">{{ ucfirst($payment->status) }}</td><td class="p-3">{{ $payment->receipt?->receipt_number ?? '—' }}</td></tr>@endforeach</tbody></table></div></section>
@endif
</div></section>
@endsection
