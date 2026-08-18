<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantStatus;
use App\Models\BibAssignmentLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\ImportBatch;
use App\Models\Participant;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $activeEvent = Event::latest()->first();
        
        $participantQuery = Participant::query();
        if ($activeEvent) {
            $participantQuery->where('event_id', $activeEvent->id);
        }

        $totalParticipants = (clone $participantQuery)->count();
        $claimedOnlyCount = (clone $participantQuery)->where('status', ParticipantStatus::Claimed)->count();
        $checkedInCount = (clone $participantQuery)->where('status', ParticipantStatus::CheckedIn)->count();

        $totalClaimed = (clone $participantQuery)->where(function ($q) {
            $q->whereNotNull('bib_number')
              ->orWhereIn('status', [ParticipantStatus::Claimed, ParticipantStatus::CheckedIn]);
        })->count();

        $totalUnclaimed = max(0, $totalParticipants - $totalClaimed);
        $claimPercentage = $totalParticipants > 0 
            ? round(($totalClaimed / $totalParticipants) * 100, 1) 
            : 0;

        // Categories summary
        $categoryQuery = Category::query();
        if ($activeEvent) {
            $categoryQuery->where('event_id', $activeEvent->id);
        }

        $categories = $categoryQuery->withCount([
            'participants as total_count',
            'participants as claimed_count' => function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('bib_number')
                      ->orWhereIn('status', [ParticipantStatus::Claimed, ParticipantStatus::CheckedIn]);
                });
            }
        ])->get()->map(function ($cat) {
            $cat->unclaimed_count = max(0, $cat->total_count - $cat->claimed_count);
            $cat->percentage = $cat->total_count > 0 
                ? round(($cat->claimed_count / $cat->total_count) * 100, 1) 
                : 0;
            return $cat;
        });

        // Recent activity feed: prefer BibAssignmentLog, fallback to recent claimed Participants
        $recentLogs = BibAssignmentLog::with(['participant.category', 'performedBy'])
            ->latest()
            ->limit(10)
            ->get();

        if ($recentLogs->isEmpty()) {
            $recentActivity = Participant::with(['category', 'claimedBy'])
                ->whereNotNull('bib_number')
                ->latest('claimed_at')
                ->limit(10)
                ->get()
                ->map(function ($p) {
                    return (object)[
                        'id' => $p->id,
                        'created_at' => $p->claimed_at ?? $p->updated_at,
                        'bib_number' => $p->bib_number,
                        'participant' => $p,
                        'performed_by' => $p->claimedBy,
                    ];
                });
        } else {
            $recentActivity = $recentLogs;
        }

        $recentBatches = ImportBatch::with('uploadedBy')
            ->latest()
            ->limit(5)
            ->get();

        // Additional Real-Time Operational Analytics (Loket Ramai, Sengketa, Jam Peak)
        $disputedCount = BibAssignmentLog::where('action', 'revoke')->count();

        // Top busiest counters / staff
        $topCounters = BibAssignmentLog::where('action', 'assign')
            ->select('performed_by', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('performed_by')
            ->orderByDesc('total')
            ->with('performedBy')
            ->limit(3)
            ->get()
            ->map(function ($log) {
                $counterNum = $log->performedBy?->counter_number;
                return [
                    'counter_name' => $counterNum ? "Loket #{$counterNum}" : ($log->performedBy?->name ?? 'Loket Standar'),
                    'staff_name' => $log->performedBy?->name ?? 'Petugas Loket',
                    'total' => $log->total,
                ];
            });

        // Peak Hours & Hourly Distribution Chart (06:00 - 20:00)
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $hourExpr = $driver === 'sqlite' ? "cast(strftime('%H', created_at) as integer)" : "HOUR(created_at)";

        $hourlyDataRaw = BibAssignmentLog::where('action', 'assign')
            ->select(\Illuminate\Support\Facades\DB::raw("{$hourExpr} as hour_slot"), \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('hour_slot')
            ->pluck('count', 'hour_slot')
            ->toArray();

        $hourlyChart = [];
        $maxCount = 0;
        for ($h = 6; $h <= 20; $h++) {
            $count = (int) ($hourlyDataRaw[$h] ?? $hourlyDataRaw[str_pad((string)$h, 2, '0', STR_PAD_LEFT)] ?? 0);
            if ($count > $maxCount) {
                $maxCount = $count;
            }
            $hourlyChart[] = [
                'hour' => str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00',
                'count' => $count,
            ];
        }

        $peakHourRow = BibAssignmentLog::where('action', 'assign')
            ->select(\Illuminate\Support\Facades\DB::raw("{$hourExpr} as hour_slot"), \Illuminate\Support\Facades\DB::raw('count(*) as total_scans'))
            ->groupBy('hour_slot')
            ->orderByDesc('total_scans')
            ->first();

        if ($peakHourRow && $peakHourRow->hour_slot !== null) {
            $h = (int) $peakHourRow->hour_slot;
            $startH = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
            $endH = str_pad((string)(($h + 1) % 24), 2, '0', STR_PAD_LEFT);
            $peakHourFormatted = "{$startH}:00 - {$endH}:00 WIB";
            $peakHourCount = $peakHourRow->total_scans;
        } else {
            $peakHourFormatted = "Belum Ada Aktivitas";
            $peakHourCount = 0;
        }

        // Full Counter Performance Breakdown
        $counterPerformance = User::whereIn('role', ['loket', 'admin'])
            ->get()
            ->map(function ($u) use ($totalClaimed) {
                $scans = BibAssignmentLog::where('performed_by', $u->id)->where('action', 'assign')->count();
                $percentage = $totalClaimed > 0 ? round(($scans / $totalClaimed) * 100, 1) : 0;
                return [
                    'id' => $u->id,
                    'counter_name' => $u->counter_number ? "Loket #{$u->counter_number}" : $u->name,
                    'staff_name' => $u->name,
                    'total_scans' => $scans,
                    'percentage' => $percentage,
                    'is_active' => (bool) $u->is_active,
                ];
            })
            ->sortByDesc('total_scans')
            ->values();

        return Inertia::render('Dashboard', [
            'activeEvent' => $activeEvent,
            'stats' => [
                'totalParticipants' => $totalParticipants,
                'totalClaimed' => $totalClaimed,
                'totalUnclaimed' => $totalUnclaimed,
                'claimPercentage' => $claimPercentage,
                'claimedOnlyCount' => $claimedOnlyCount,
                'checkedInCount' => $checkedInCount,
                'disputedCount' => $disputedCount,
                'peakHourFormatted' => $peakHourFormatted,
                'peakHourCount' => $peakHourCount,
            ],
            'topCounters' => $topCounters,
            'counterPerformance' => $counterPerformance,
            'hourlyChart' => $hourlyChart,
            'maxHourlyCount' => max(1, $maxCount),
            'categories' => $categories,
            'recentActivity' => $recentActivity,
            'recentBatches' => $recentBatches,
        ]);
    }
}
