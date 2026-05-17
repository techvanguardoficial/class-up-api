<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function index()
    {
        return response()->json(Guardian::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string',
            'cpf' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $guardian = Guardian::create($validated);
        return response()->json($guardian, 201);
    }

    public function show(Guardian $guardian)
    {
        return response()->json($guardian);
    }

    public function update(Request $request, Guardian $guardian)
    {
        $validated = $request->validate([
            'user_id' => 'exists:users,id',
            'cpf' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $guardian->update($validated);
        return response()->json($guardian);
    }

    public function destroy(Guardian $guardian)
    {
        $guardian->delete();
        return response()->json(null, 204);
    }

    public function subscriptions(Request $request)
    {
        $user = $request->user();
        $guardian = Guardian::where('user_id', $user->id)->first();

        if (!$guardian) {
            return response()->json(['message' => 'Responsável não encontrado.'], 404);
        }

        $students = $guardian->students()->with('studentPaymentPlans.schoolPaymentPlan')->get();

        $subscriptions = [];
        $totalPrice = 0;

        foreach ($students as $student) {
            foreach ($student->studentPaymentPlans as $paymentPlan) {
                $subscriptions[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'payment_plan_id' => $paymentPlan->id,
                    'plan_name' => $paymentPlan->schoolPaymentPlan->name,
                    'plan_description' => $paymentPlan->schoolPaymentPlan->description,
                    'price' => $paymentPlan->schoolPaymentPlan->price,
                    'due_day' => $paymentPlan->getDueDay(),
                    'start_date' => $paymentPlan->start_date,
                    'end_date' => $paymentPlan->end_date,
                    'active' => $paymentPlan->active,
                ];
                $totalPrice += $paymentPlan->schoolPaymentPlan->price;
            }
        }

        return response()->json([
            'subscriptions' => $subscriptions,
            'total_price' => $totalPrice,
            'count' => count($subscriptions),
        ]);
    }
}
