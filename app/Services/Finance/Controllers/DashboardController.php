<?php

namespace App\Services\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Payment\Model\Payment;
use App\Services\Sppd\Model\Sppd;
use App\Services\Sppd\Model\SppdWilayah;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $scopeAllData = in_array($user->role, ['admin', 'finance'], true);

        $sppdBaseQuery = Sppd::query();
        if (!$scopeAllData) {
            $sppdBaseQuery->where('user_id', $user->id);
        }

        $currentMonthStart = now()->startOfMonth();
        $previousMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();

        $summary = [
            'total_sppd_this_month' => (clone $sppdBaseQuery)
                ->whereBetween('created_at', [$currentMonthStart, now()])
                ->count(),
            'in_progress' => (clone $sppdBaseQuery)
                ->where('status', 'Pending')
                ->count(),
            'approved' => (clone $sppdBaseQuery)
                ->where('status', 'Approved')
                ->count(),
            'rejected' => (clone $sppdBaseQuery)
                ->where('status', 'Rejected')
                ->count(),
            'completed' => (clone $sppdBaseQuery)
                ->where('status', 'Completed')
                ->count(),
            'total_users' => $scopeAllData
                ? \App\Models\User::count()
                : 1,
        ];

        $summary['month_over_month'] = [
            'total_sppd_this_month' => $this->percentageChange(
                (clone $sppdBaseQuery)->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->count(),
                $summary['total_sppd_this_month']
            ),
            'approved' => $this->percentageChange(
                (clone $sppdBaseQuery)->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->where('status', 'Approved')->count(),
                $summary['approved']
            ),
            'rejected' => $this->percentageChange(
                (clone $sppdBaseQuery)->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->where('status', 'Rejected')->count(),
                $summary['rejected']
            ),
        ];

        $monthlySppd = $this->buildMonthlySeries(
            (clone $sppdBaseQuery)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as aggregate")
                ->groupBy('month_key')
                ->orderBy('month_key')
                ->get(),
            'aggregate'
        );

        $paymentQuery = Payment::query()
            ->where('status', 'PAID')
            ->when(!$scopeAllData, function ($query) use ($user) {
                $query->whereHas('sppd', function ($sppdQuery) use ($user) {
                    $sppdQuery->where('user_id', $user->id);
                });
            });

        $monthlySpending = $this->buildMonthlySeries(
            $paymentQuery
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COALESCE(SUM(amount), 0) as aggregate")
                ->groupBy('month_key')
                ->orderBy('month_key')
                ->get(),
            'aggregate'
        );

        $statusBreakdown = [
            'Approved' => (clone $sppdBaseQuery)->where('status', 'Approved')->count(),
            'Pending' => (clone $sppdBaseQuery)->where('status', 'Pending')->count(),
            'Rejected' => (clone $sppdBaseQuery)->where('status', 'Rejected')->count(),
        ];

        $latestSppds = (clone $sppdBaseQuery)
            ->with(['user', 'payments'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Sppd $sppd) {
                return [
                    'id' => $sppd->id,
                    'nomor_sppd' => $sppd->nomor_sppd,
                    'employee_name' => optional($sppd->user)->name,
                    'submission_date' => optional($sppd->created_at)?->format('Y-m-d'),
                    'purpose' => $sppd->keperluan ?: $sppd->tujuan,
                    'status' => $sppd->status,
                    'amount' => (float) ($sppd->biaya_realisasi ?? $sppd->biaya_estimasi ?? 0),
                ];
            })
            ->values();

        $topProvincesQuery = SppdWilayah::query()
            ->select('province_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('province_id')
            ->when(!$scopeAllData, function ($query) use ($user) {
                $query->whereHas('sppd', function ($sppdQuery) use ($user) {
                    $sppdQuery->where('user_id', $user->id);
                });
            })
            ->groupBy('province_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topProvinces = $topProvincesQuery->map(function ($row) {
            $province = \App\Services\Booking\Model\Province::query()->find($row->province_id);

            return [
                'province_id' => $row->province_id,
                'name' => $province?->name ?? 'Unknown Province',
                'total' => (int) $row->total,
            ];
        })->values();

        return response()->json([
            'summary' => $summary,
            'charts' => [
                'monthly_sppd' => $monthlySppd,
                'monthly_spending' => $monthlySpending,
                'status_breakdown' => $statusBreakdown,
            ],
            'latest_sppds' => $latestSppds,
            'top_provinces' => $topProvinces,
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'scope' => $scopeAllData ? 'all' : 'self',
            ],
        ]);
    }

    private function buildMonthlySeries(Collection $rows, string $valueKey): array
    {
        $months = collect(range(11, 0))
            ->map(function (int $offset) {
                $date = now()->copy()->subMonthsNoOverflow($offset);

                return [
                    'key' => $date->format('Y-m'),
                    'label' => $date->translatedFormat('M Y'),
                    'short_label' => $date->translatedFormat('M'),
                ];
            })
            ->values();

        $indexed = $rows->keyBy('month_key');

        return [
            'labels' => $months->pluck('short_label')->all(),
            'values' => $months->map(function (array $month) use ($indexed, $valueKey) {
                return (float) ($indexed->get($month['key'])->{$valueKey} ?? 0);
            })->all(),
        ];
    }

    private function percentageChange(int|float $previous, int|float $current): float
    {
        if ((float) $previous === 0.0) {
            return (float) ($current > 0 ? 100 : 0);
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
