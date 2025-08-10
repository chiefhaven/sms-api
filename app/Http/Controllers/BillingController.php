<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Http\Requests\StoreBillingRequest;
use App\Http\Requests\UpdateBillingRequest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;
use DB;

class BillingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('billing.bills');
    }

    public function fetchBills(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search.value', '');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['bill_number', null, 'type', 'total_amount', 'status', 'date']; // match columns in JS table

        $query = Billing::with('client');

        // Total records count before filtering
        $totalData = $query->count();

        // Filter by status if provided
        if ($status) {
            $query->where('status', $status);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%$search%")
                  ->orWhereHas('client', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  })
                  ->orWhere('type', 'like', "%$search%");
            });
        }

        // Count after filtering
        $filteredData = $query->count();

        // Apply ordering
        if (isset($columns[$orderColumnIndex]) && $columns[$orderColumnIndex]) {
            $query->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Apply pagination
        $billings = $query->offset($start)->limit($length)->get();

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalData,
            "recordsFiltered" => $filteredData,
            "data" => $billings,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:Receipt,Invoice,Quotation',
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $billNumber = '';
        DB::transaction(function () use ($request, &$billNumber, &$bill) {
            // Generate bill_number (e.g., BILL20250810-0001)
            $datePart = now()->format('Ymd');
            $lastBill = Billing::whereDate('created_at', now()->toDateString())->latest()->first();
            $nextNumber = $lastBill ? ((int)substr($lastBill->bill_number, -4) + 1) : 1;
            $billNumber = 'BILL' . $datePart . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $billData = $request->only([
                'client_id', 'type', 'date', 'due_date', 'notes', 'total_amount', 'items'
            ]);
            $billData['bill_number'] = $billNumber;
            $billData['status'] = 'active';

            $bill = Billing::create($billData);

            // Update client balance only if type is 'Receipt'
            if ($bill && $bill->type === 'Receipt') {
                $client = Client::find($bill->client_id);
                if ($client) {
                    $client->account_balance += $bill->total_amount;
                    $client->save();
                }
            }
        });

        return response()->json(['message' => 'Bill created successfully', 'data' => $bill], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Billing $billing)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Billing $billing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $bill = Billing::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'bill_number' => 'required|string|unique:billings,bill_number,' . $bill->id,
            'type' => 'required|in:Receipt,Invoice,Quotation',
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $oldTotal = $bill->total_amount;
        $oldClientId = $bill->client_id;  // Keep old client id
        $oldType = $bill->type;

        // Fill only allowed fields (no client_id)
        $bill->fill($request->only([
            'bill_number', 'type', 'date', 'due_date', 'notes', 'status', 'total_amount'
        ]));
        $bill->items = $request->items;
        $bill->save();

        $newTotal = $bill->total_amount;
        $newType = $bill->type;

        // Since client cannot change, only handle balance update for type changes and total amount changes for the same client

        if ($oldType === 'Receipt' && $newType !== 'Receipt') {
            $client = Client::find($oldClientId);
            if ($client) {
                $client->account_balance -= $oldTotal;
                $client->save();
            }
        }

        if ($oldType !== 'Receipt' && $newType === 'Receipt') {
            $client = Client::find($oldClientId);
            if ($client) {
                $client->account_balance += $newTotal;
                $client->save();
            }
        }

        if ($oldType === 'Receipt' && $newType === 'Receipt' && $oldTotal != $newTotal) {
            $diff = $newTotal - $oldTotal;
            $client = Client::find($oldClientId);
            if ($client) {
                $client->account_balance += $diff;
                $client->save();
            }
        }

        return response()->json(['message' => 'Bill updated successfully', 'data' => $bill]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $bill = Billing::findOrFail($id);

        $client = Client::find($bill->client_id);

        if ($bill->type == 'Receipt' && $client) {
            $client->account_balance -= $bill->total_amount;
            $client->save();
        }

        $bill->delete();

        return redirect()->route('billing')->with('success', 'Bill deleted successfully');
    }
}
