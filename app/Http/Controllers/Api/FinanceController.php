<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use DB;

class FinanceController extends Controller
{
    /**
     * Return financial summary based on school payment plans.
     *
     * Query params:
     *   - months (int, default 12): look-back window in months
     */
    public function summary(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $months = (int) $request->query('months', 12);
        $months = min(max($months, 1), 24);
        $since = now()->subMonths($months)->startOfMonth();

        // Total expected revenue (active subscriptions)
        $totalExpected = \App\Models\StudentPaymentPlan::join('school_payment_plans', 'student_payment_plans.school_payment_plan_id', '=', 'school_payment_plans.id')
            ->where('school_payment_plans.school_id', $schoolId)
            ->where('student_payment_plans.active', true)
            ->sum('school_payment_plans.price');

        // Total revenue (paid payments)
        $totalRevenue = Payment::join('school_payment_plans', 'payments.school_payment_plan_id', '=', 'school_payment_plans.id')
            ->where('school_payment_plans.school_id', $schoolId)
            ->where('payments.status', 'paid')
            ->where('payments.paid_date', '>=', $since)
            ->sum('payments.amount');

        // Total overdue (late payments)
        $totalOverdue = Payment::join('school_payment_plans', 'payments.school_payment_plan_id', '=', 'school_payment_plans.id')
            ->where('school_payment_plans.school_id', $schoolId)
            ->where('payments.status', 'late')
            ->sum('payments.amount');

        // Total pending (pending payments)
        $totalPending = Payment::join('school_payment_plans', 'payments.school_payment_plan_id', '=', 'school_payment_plans.id')
            ->where('school_payment_plans.school_id', $schoolId)
            ->where('payments.status', 'pending')
            ->sum('payments.amount');

        // Monthly breakdown: subscriptions vs payments vs overdue
        $monthly = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $monthKey = $monthStart->format('Y-m');

            // Subscriptions active during this month
            $subscriptions = \App\Models\StudentPaymentPlan::join('school_payment_plans', 'student_payment_plans.school_payment_plan_id', '=', 'school_payment_plans.id')
                ->where('school_payment_plans.school_id', $schoolId)
                ->where('student_payment_plans.active', true)
                ->where('student_payment_plans.start_date', '<=', $monthEnd)
                ->where(function($q) use ($monthStart) {
                    $q->whereNull('student_payment_plans.end_date')
                      ->orWhere('student_payment_plans.end_date', '>=', $monthStart);
                })
                ->sum('school_payment_plans.price');

            // Payments received during this month
            $revenue = Payment::join('school_payment_plans', 'payments.school_payment_plan_id', '=', 'school_payment_plans.id')
                ->where('school_payment_plans.school_id', $schoolId)
                ->where('payments.status', 'paid')
                ->whereBetween('payments.paid_date', [$monthStart, $monthEnd])
                ->sum('payments.amount');

            // Overdue (active plans before this month with no paid payment in that month)
            $activeStudentPlans = \App\Models\StudentPaymentPlan::join('school_payment_plans', 'student_payment_plans.school_payment_plan_id', '=', 'school_payment_plans.id')
                ->where('school_payment_plans.school_id', $schoolId)
                ->where('student_payment_plans.active', true)
                ->where('student_payment_plans.start_date', '<', $monthStart)
                ->where(function($q) use ($monthStart) {
                    $q->whereNull('student_payment_plans.end_date')
                      ->orWhere('student_payment_plans.end_date', '>=', $monthStart);
                })
                ->select('student_payment_plans.student_id', 'school_payment_plans.price')
                ->get();

            $overdue = 0;
            foreach ($activeStudentPlans as $plan) {
                $hasPaid = Payment::where('student_id', $plan->student_id)
                    ->whereBetween('paid_date', [$monthStart, $monthEnd])
                    ->where('status', 'paid')
                    ->exists();

                if (!$hasPaid) {
                    $overdue += $plan->price;
                }
            }

            $subscriptionsAmount = round($subscriptions ?? 0, 2);
            $revenueAmount = round($revenue ?? 0, 2);
            $overdueAmount = round($overdue ?? 0, 2);
            $pending = $subscriptionsAmount - $revenueAmount;

            $monthly[] = [
                'month'           => $monthKey,
                'subscriptions'   => $subscriptionsAmount,
                'revenue'         => $revenueAmount,
                'overdue'         => $overdueAmount,
                'pending'         => $pending,
                'conversion_rate' => $subscriptionsAmount > 0 ? round(($revenueAmount / $subscriptionsAmount) * 100, 2) : 0,
            ];
        }

        // Payment methods breakdown
        $byMethod = Payment::join('school_payment_plans', 'payments.school_payment_plan_id', '=', 'school_payment_plans.id')
            ->where('school_payment_plans.school_id', $schoolId)
            ->where('payments.status', 'paid')
            ->where('payments.paid_date', '>=', $since)
            ->leftJoin('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->groupBy('payment_methods.name')
            ->select(
                'payment_methods.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(payments.amount) as total')
            )
            ->get()
            ->map(fn($row) => [
                'method' => $row->name ?? 'Sem método',
                'count'  => $row->count,
                'total'  => round($row->total, 2),
            ]);

        // Plans breakdown
        $byPlan = Payment::join('school_payment_plans', 'payments.school_payment_plan_id', '=', 'school_payment_plans.id')
            ->where('school_payment_plans.school_id', $schoolId)
            ->where('payments.status', 'paid')
            ->where('payments.paid_date', '>=', $since)
            ->groupBy('school_payment_plans.name')
            ->select(
                'school_payment_plans.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(payments.amount) as total')
            )
            ->get()
            ->map(fn($row) => [
                'plan'  => $row->name,
                'count' => $row->count,
                'total' => round($row->total, 2),
            ]);

        // Students with late payments
        $studentsWithLatePay = Payment::join('school_payment_plans', 'payments.school_payment_plan_id', '=', 'school_payment_plans.id')
            ->where('school_payment_plans.school_id', $schoolId)
            ->where('payments.status', 'late')
            ->select('payments.student_id')
            ->distinct()
            ->count();

        return response()->json([
            'currency'                => 'BRL',
            'total_expected'          => round($totalExpected ?? 0, 2),
            'total_revenue'           => round($totalRevenue ?? 0, 2),
            'total_overdue'           => round($totalOverdue ?? 0, 2),
            'total_pending'           => round($totalPending ?? 0, 2),
            'conversion_rate'         => $totalExpected > 0 ? round(($totalRevenue / $totalExpected) * 100, 2) : 0,
            'students_with_late_pay'  => $studentsWithLatePay,
            'monthly'                 => $monthly,
            'by_method'               => $byMethod,
            'by_plan'                 => $byPlan,
        ]);
    }
}
