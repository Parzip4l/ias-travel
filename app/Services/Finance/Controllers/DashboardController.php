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
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $scopeAllData = $this->canViewAllDashboardData($user);

        $sppdBaseQuery = Sppd::query();
        if (!$scopeAllData) {
            $sppdBaseQuery->where('user_id', $user->id);
        }

        $startDate = $request->filled('start_date')
            ? Carbon::parse((string) $request->string('start_date'))->startOfDay()
            : null;
        $endDate = $request->filled('end_date')
            ? Carbon::parse((string) $request->string('end_date'))->endOfDay()
            : null;

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            return response()->json([
                'message' => 'Rentang tanggal tidak valid. Tanggal mulai harus sebelum atau sama dengan tanggal akhir.',
            ], 422);
        }

        $sppdFilteredQuery = $this->applyDateRangeFilter(clone $sppdBaseQuery, $startDate, $endDate);

        $currentMonthStart = now()->startOfMonth();
        $previousMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();

        $summary = [
            'total_sppd_this_month' => $startDate || $endDate
                ? (clone $sppdFilteredQuery)->count()
                : (clone $sppdBaseQuery)
                    ->whereBetween(DB::raw('COALESCE(tanggal_berangkat, created_at)'), [$currentMonthStart, now()])
                    ->count(),
            'in_progress' => $this->countByStatus((clone $sppdFilteredQuery), 'PENDING'),
            'approved' => $this->countByStatus((clone $sppdFilteredQuery), 'APPROVED'),
            'rejected' => $this->countByStatus((clone $sppdFilteredQuery), 'REJECTED'),
            'completed' => $this->countByStatus((clone $sppdFilteredQuery), 'COMPLETED'),
            'total_users' => $scopeAllData
                ? (clone $sppdFilteredQuery)->distinct('user_id')->count('user_id')
                : 1,
        ];

        $summary['month_over_month'] = [
            'total_sppd_this_month' => $this->percentageChange(
                (clone $sppdBaseQuery)->whereBetween(DB::raw('COALESCE(tanggal_berangkat, created_at)'), [$previousMonthStart, $previousMonthEnd])->count(),
                $summary['total_sppd_this_month']
            ),
            'approved' => $this->percentageChange(
                $this->countByStatus(
                    (clone $sppdBaseQuery)->whereBetween(DB::raw('COALESCE(tanggal_berangkat, created_at)'), [$previousMonthStart, $previousMonthEnd]),
                    'APPROVED'
                ),
                $summary['approved']
            ),
            'rejected' => $this->percentageChange(
                $this->countByStatus(
                    (clone $sppdBaseQuery)->whereBetween(DB::raw('COALESCE(tanggal_berangkat, created_at)'), [$previousMonthStart, $previousMonthEnd]),
                    'REJECTED'
                ),
                $summary['rejected']
            ),
        ];

        $monthlySppd = $this->buildMonthlySeries(
            (clone $sppdFilteredQuery)
                ->selectRaw("DATE_FORMAT(COALESCE(tanggal_berangkat, created_at), '%Y-%m') as month_key, COUNT(*) as aggregate")
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

        if ($startDate || $endDate) {
            $paymentQuery->whereHas('sppd', function ($sppdQuery) use ($startDate, $endDate) {
                $this->applyDateRangeFilter($sppdQuery, $startDate, $endDate);
            });
        }

        $monthlySpending = $this->buildMonthlySeries(
            $paymentQuery
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COALESCE(SUM(amount), 0) as aggregate")
                ->groupBy('month_key')
                ->orderBy('month_key')
                ->get(),
            'aggregate'
        );

        $statusBreakdown = [
            'Approved' => $this->countByStatus((clone $sppdFilteredQuery), 'APPROVED'),
            'Pending' => $this->countByStatus((clone $sppdFilteredQuery), 'PENDING'),
            'Rejected' => $this->countByStatus((clone $sppdFilteredQuery), 'REJECTED'),
        ];

        $latestSppds = (clone $sppdFilteredQuery)
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
            ->when(!$scopeAllData, function ($query) use ($user, $scopeAllData) {
                $query->whereHas('sppd', function ($sppdQuery) use ($user, $scopeAllData) {
                    if (!$scopeAllData) {
                        $sppdQuery->where('user_id', $user->id);
                    }
                });
            })
            ->when($startDate || $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereHas('sppd', function ($sppdQuery) use ($startDate, $endDate) {
                    $this->applyDateRangeFilter($sppdQuery, $startDate, $endDate);
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
                'filters' => [
                    'start_date' => $startDate?->toDateString(),
                    'end_date' => $endDate?->toDateString(),
                ],
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

    private function canViewAllDashboardData(object $user): bool
    {
        $role = strtolower((string) ($user->role ?? ''));

        if (in_array($role, ['admin', 'finance', 'superadmin'], true)) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('finance') || $user->hasRole('superadmin');
        }

        return false;
    }

    private function countByStatus($query, string $status): int
    {
        return (clone $query)
            ->whereRaw('UPPER(status) = ?', [$status])
            ->count();
    }

    private function applyDateRangeFilter($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->where(DB::raw('COALESCE(tanggal_berangkat, created_at)'), '>=', $startDate);
        }

        if ($endDate) {
            $query->where(DB::raw('COALESCE(tanggal_berangkat, created_at)'), '<=', $endDate);
        }

        return $query;
    }
}
