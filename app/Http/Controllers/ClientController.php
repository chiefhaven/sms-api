<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;

use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('clients.clients');
    }

    public function fetchClients(Request $request): JsonResponse
    {
        $search = $request->input('search.value');
        $status = $request->input('status');

        $clients = Client::with('user')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('company', 'like', "%$search%")
                      ->orWhere('phone', 'like', "%$search%")
                      ->orWhere('sender_id', 'like', "%$search%")
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('email', 'like', "%$search%");
                      });
                });
            })
            ->orderByDesc('created_at');

        return DataTables::of($clients)
            ->addColumn('account_balance', fn($client) => $client->account_balance)
            ->addColumn('status', fn($client) => $client->status)
            ->addColumn('email', fn($client) => optional($client->user)->email ?? 'N/A')
            ->addColumn('create_date', fn($client) => $client->created_at)
            ->addColumn('actions', function ($client) {
                return $client->id; // Will be handled in frontend
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,pending',
            'completion_notes' => 'nullable|string'
        ]);

        $client = Client::findOrFail($id);
        $client->update([
            'status' => $request->status,
            'completion_notes' => $request->completion_notes
        ]);

        return response()->json(['success' => true]);
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
    public function store(StoreClientRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(['success' => true]);
    }
}
