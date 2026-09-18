<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Signal;
use App\Models\SignalAnalysisRun;
use App\Models\SignalDelivery;
use App\Models\SignalDistribution;
use App\Models\SignalEvent;
use App\Models\Stock;
use App\Models\User;
use App\Services\SignalAdminService;
use App\Services\SignalDistributionService;
use App\Services\SignalReanalysisService;
use App\Services\MarketInstrumentContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SignalController extends Controller
{
    public function index()
    {
        $stats = [
            'ready' => Signal::query()->where('status', 'ready')->count(),
            'published' => Signal::query()->where('status', 'published')->count(),
            'active' => Signal::query()->where('status', 'active')->count(),
            'terminal' => Signal::query()->whereIn('status', Signal::TERMINAL_STATUSES)->count(),
            'deliveries' => SignalDelivery::query()->count(),
            'recipients' => SignalDelivery::query()->distinct()->count('user_id'),
            'today' => Signal::query()->whereDate('generated_at', today())->count(),
        ];

        $latest = Signal::query()
            ->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets'])
            ->latest('id')
            ->limit(8)
            ->get();

        $recentEvents = SignalEvent::query()
            ->with(['signal.stock', 'signal.marketInstrument.stock', 'signal.marketInstrument.forexPair', 'actor'])
            ->latest('occurred_at')
            ->limit(8)
            ->get();

        $stocks = Stock::query()->active()->orderBy('symbol')->get(['id', 'symbol', 'company_name']);

        return view('admin.signals.index', compact('stats', 'latest', 'recentEvents', 'stocks'));
    }

    public function candidates()
    {
        return $this->listing(
            ['candidate', 'ready'],
            'Generated Candidates',
            'Qualified opportunities waiting for administrator publication.',
            'sparkles'
        );
    }

    public function live()
    {
        return $this->listing(
            ['published', 'active'],
            'Live Signals',
            'Published opportunities currently visible to the distribution engine.',
            'radio-tower'
        );
    }

    public function history()
    {
        return $this->listing(
            Signal::TERMINAL_STATUSES,
            'Signal History',
            'Closed, stopped, expired, invalidated and cancelled Signal records.',
            'history'
        );
    }

    public function recipients(Request $request)
    {
        $scope = in_array($request->query('scope'), ['individual', 'general'], true)
            ? (string) $request->query('scope')
            : 'all';
        $mode = in_array($request->query('mode'), ['membership', 'complimentary'], true)
            ? (string) $request->query('mode')
            : 'all';
        $status = in_array($request->query('status'), array_merge(Signal::OPEN_STATUSES, Signal::TERMINAL_STATUSES), true)
            ? (string) $request->query('status')
            : 'all';
        $search = trim((string) $request->query('search', ''));
        $signalId = $request->integer('signal_id');

        $query = SignalDelivery::query()
            ->with(['user', 'signal.stock', 'signal.marketInstrument.stock', 'signal.marketInstrument.forexPair', 'distribution.initiatedBy'])
            ->latest('delivered_at')
            ->latest('id');

        if ($scope !== 'all') {
            $this->applyRecipientScope($query, $scope);
        }

        if ($mode !== 'all') {
            $query->whereHas('distribution', fn ($distribution) => $distribution->where('mode', $mode));
        }

        if ($status !== 'all') {
            $query->whereHas('signal', fn ($signal) => $signal->where('status', $status));
        }

        if ($signalId > 0) {
            $query->where('signal_id', $signalId);
        }

        if ($search !== '') {
            $query->where(function ($subquery) use ($search) {
                $subquery
                    ->whereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('signal.stock', fn ($stock) => $stock
                        ->where('symbol', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('signal.marketInstrument', fn ($instrument) => $instrument
                        ->where('symbol', 'like', "%{$search}%")
                        ->orWhere('display_symbol', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $deliveries = $query->paginate(30)->withQueryString();

        $individualQuery = SignalDelivery::query();
        $this->applyRecipientScope($individualQuery, 'individual');
        $generalQuery = SignalDelivery::query();
        $this->applyRecipientScope($generalQuery, 'general');

        $stats = [
            'deliveries' => SignalDelivery::query()->count(),
            'current' => SignalDelivery::query()->whereHas('signal', fn ($signal) => $signal->whereIn('status', ['published', 'active']))->count(),
            'customers' => SignalDelivery::query()->distinct()->count('user_id'),
            'individual' => $individualQuery->count(),
            'general' => $generalQuery->count(),
        ];

        return view('admin.signals.recipients', compact(
            'deliveries',
            'stats',
            'scope',
            'mode',
            'status',
            'search',
            'signalId'
        ));
    }

    public function activity()
    {
        $events = SignalEvent::query()
            ->with(['signal.stock', 'signal.marketInstrument.stock', 'signal.marketInstrument.forexPair', 'actor'])
            ->latest('occurred_at')
            ->limit(40)
            ->get();

        $analysisRuns = SignalAnalysisRun::query()
            ->with(['signal.stock', 'signal.marketInstrument.stock', 'signal.marketInstrument.forexPair', 'stock', 'marketInstrument', 'actor'])
            ->latest('analyzed_at')
            ->limit(30)
            ->get();

        $distributions = SignalDistribution::query()
            ->with(['signal.stock', 'signal.marketInstrument.stock', 'signal.marketInstrument.forexPair', 'initiatedBy'])
            ->latest('id')
            ->limit(30)
            ->get();

        return view('admin.signals.activity', compact('events', 'analysisRuns', 'distributions'));
    }

    public function show(Signal $signal, MarketInstrumentContextService $marketContext)
    {
        $signal->load([
            'stock',
            'marketInstrument.stock',
            'marketInstrument.forexPair',
            'targets',
            'analysisRuns' => fn ($query) => $query->latest('analyzed_at'),
            'revisions' => fn ($query) => $query->latest('revision_number'),
            'events' => fn ($query) => $query->latest('occurred_at'),
            'distributions' => fn ($query) => $query->withCount('deliveries')->latest('id'),
            'createdBy',
            'updatedBy',
        ]);

        $signal->loadCount('deliveries');

        $customers = User::query()
            ->where('is_admin', false)
            ->orderBy('name')
            ->limit(250)
            ->get(['id', 'name', 'email', 'account_status']);

        try {
            $analysis = $marketContext->forSignal($signal);

            if (count((array) ($analysis['timeframes'][$signal->timeframe] ?? [])) >= 2) {
                $analysis['default_timeframe'] = $signal->timeframe;
            }
        } catch (\Throwable) {
            $analysis = [
                'source' => $signal->marketplace === 'controlled' ? 'controlled_market_unavailable' : 'signal_room_unavailable',
                'current_price' => (float) ($signal->entry_min ?? 0),
                'previous_close' => (float) ($signal->entry_min ?? 0),
                'has_chart' => false,
                'timeframes' => [],
                'default_timeframe' => $signal->timeframe,
            ];
        }

        return view('admin.signals.show', compact('signal', 'customers', 'analysis'));
    }

    public function store(Request $request, SignalAdminService $service)
    {
        $data = $this->validateSignalTerms($request, true);

        try {
            $signal = $service->createManual($data, Auth::user());
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['signal' => $e->getMessage()]);
        }

        return redirect()->route('admin.signals.show', $signal)
            ->with('success', "Manual Signal #{$signal->id} created in ready state.");
    }

    public function update(Request $request, Signal $signal, SignalAdminService $service)
    {
        $data = $this->validateSignalTerms($request, false);

        try {
            $revision = $service->updateTerms($signal, $data, Auth::user());
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['signal' => $e->getMessage()]);
        }

        return back()->with('success', $revision
            ? "Signal terms updated as revision #{$revision->revision_number}."
            : 'No material Signal change was detected.');
    }

    public function reanalyze(Signal $signal, SignalReanalysisService $service)
    {
        try {
            $result = $service->reanalyze($signal, true, 'admin', Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['signal' => $e->getMessage()]);
        }

        return back()->with('success', 'Re-analysis: '.strtoupper((string) ($result['status'] ?? 'completed')).' — '.($result['reason'] ?? 'completed.'));
    }

    public function publish(Signal $signal, SignalAdminService $service)
    {
        try {
            $service->publish($signal, Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['signal' => $e->getMessage()]);
        }

        return back()->with('success', 'Signal published. Distribution remains an explicit administrator action.');
    }

    public function distribute(Signal $signal, SignalDistributionService $service)
    {
        try {
            $distribution = $service->distributeMembership($signal, Auth::user());
        } catch (\Throwable $e) {
            return back()->withErrors(['signal' => $e->getMessage()]);
        }

        return back()->with('success', "Membership distribution complete: {$distribution->delivered_count} delivered, {$distribution->skipped_count} skipped, {$distribution->failed_count} failed.");
    }

    public function complimentary(Request $request, Signal $signal, SignalDistributionService $service)
    {
        $data = $request->validate([
            'scope' => ['required', Rule::in(['all', 'specific'])],
            'user_id' => ['nullable', 'required_if:scope,specific', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $userIds = $data['scope'] === 'specific' ? [(int) $data['user_id']] : null;

        try {
            $distribution = $service->distributeComplimentary(
                $signal,
                Auth::user(),
                $userIds,
                $data['reason'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['signal' => $e->getMessage()]);
        }

        return back()->with('success', "Complimentary distribution complete: {$distribution->delivered_count} delivered, {$distribution->skipped_count} skipped, {$distribution->failed_count} failed.");
    }

    public function cancel(Request $request, Signal $signal, SignalAdminService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        try {
            $service->cancel($signal, Auth::user(), $data['reason'] ?? null);
        } catch (\Throwable $e) {
            return back()->withErrors(['signal' => $e->getMessage()]);
        }

        return back()->with('success', 'Signal cancelled.');
    }

    public function close(Request $request, Signal $signal, SignalAdminService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        try {
            $service->close($signal, Auth::user(), $data['reason'] ?? null);
        } catch (\Throwable $e) {
            return back()->withErrors(['signal' => $e->getMessage()]);
        }

        return back()->with('success', 'Signal manually closed.');
    }

    private function listing(array $statuses, string $title, string $description, string $icon)
    {
        $signals = Signal::query()
            ->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets'])
            ->withCount(['analysisRuns', 'revisions', 'deliveries'])
            ->whereIn('status', $statuses)
            ->latest('generated_at')
            ->latest('id')
            ->paginate(30);

        return view('admin.signals.list', compact('signals', 'title', 'description', 'icon', 'statuses'));
    }

    private function applyRecipientScope($query, string $scope): void
    {
        if ($scope === 'individual') {
            $query->whereHas('distribution', function ($distribution) {
                $distribution
                    ->where('mode', 'complimentary')
                    ->where(function ($audience) {
                        $audience
                            ->where('audience_snapshot->audience_scope', 'individual')
                            ->orWhere(function ($legacy) {
                                $legacy
                                    ->whereNull('audience_snapshot->audience_scope')
                                    ->where('audience_snapshot->requested_recipients', 1);
                            });
                    });
            });
            return;
        }

        $query->whereHas('distribution', function ($distribution) {
            $distribution
                ->where('audience_snapshot->audience_scope', 'general')
                ->orWhere(function ($legacyMembership) {
                    $legacyMembership
                        ->whereNull('audience_snapshot->audience_scope')
                        ->where('mode', 'membership');
                })
                ->orWhere(function ($legacyComplimentary) {
                    $legacyComplimentary
                        ->whereNull('audience_snapshot->audience_scope')
                        ->where('mode', 'complimentary')
                        ->where('audience_snapshot->requested_recipients', '!=', 1);
                });
        });
    }

    private function validateSignalTerms(Request $request, bool $creating): array
    {
        $rules = [
            'direction' => ['required', Rule::in(['buy', 'sell'])],
            'timeframe' => ['required', Rule::in(['5m', '15m', '1h', '4h', '1d', '1w'])],
            'strength' => ['required', Rule::in(['moderate', 'strong', 'very_strong'])],
            'confluence_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'entry_min' => ['required', 'numeric', 'gt:0'],
            'entry_max' => ['required', 'numeric', 'gt:0'],
            'stop_loss' => ['required', 'numeric', 'gt:0'],
            'tp1' => ['required', 'numeric', 'gt:0'],
            'tp2' => ['nullable', 'numeric', 'gt:0'],
            'tp3' => ['nullable', 'numeric', 'gt:0'],
            'expires_at' => ['required', 'date', 'after:now'],
            'rationale' => ['nullable', 'string', 'max:5000'],
            'revision_reason' => ['nullable', 'string', 'max:1000'],
        ];

        if ($creating) {
            $rules = array_merge([
                'stock_id' => ['required', 'integer', 'exists:stocks,id'],
                'marketplace' => ['required', Rule::in(['live', 'controlled'])],
            ], $rules);
        }

        return $request->validate($rules);
    }
}
