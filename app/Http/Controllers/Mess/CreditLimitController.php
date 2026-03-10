<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\CreditLimit;
use App\Models\User;

class CreditLimitController extends Controller
{
    public function index()
    {
        $creditLimits = CreditLimit::with('user')->paginate(20);
        return view('admin.mess.credit-limits.index', compact('creditLimits'));
    }

    public function create()
    {
        $users = User::all();
        return view('admin.mess.credit-limits.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:user_credentials,pk',
            'client_type' => 'required|string',
            'credit_limit' => 'required|numeric|min:0',
            'current_balance' => 'nullable|numeric'
        ]);

        CreditLimit::create($request->all());
        return redirect()->route('admin.mess.credit-limits.index')->with('success', 'Credit limit created successfully.');
    }

    public function show($id)
    {
        $creditLimit = CreditLimit::with('user')->findOrFail($id);
        return view('admin.mess.credit-limits.show', compact('creditLimit'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $creditLimit = CreditLimit::findOrFail($id);
        $users = User::all();
        return view('admin.mess.credit-limits.edit', compact('creditLimit', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $creditLimit = CreditLimit::findOrFail($id);

        $request->validate([
            'user_id' => 'required|exists:user_credentials,pk',
            'client_type' => 'required|string',
            'credit_limit' => 'required|numeric|min:0',
            'current_balance' => 'nullable|numeric'
        ]);

        $creditLimit->update([
            'user_id' => $request->user_id,
            'client_type' => $request->client_type,
            'credit_limit' => $request->credit_limit,
            'current_balance' => $request->current_balance ?? 0,
            'remarks' => $request->remarks,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('admin.mess.credit-limits.index')
            ->with('success', 'Credit limit updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $creditLimit = CreditLimit::findOrFail($id);
        $creditLimit->delete();

        return redirect()->route('admin.mess.credit-limits.index')
            ->with('success', 'Credit limit deleted successfully.');
    }
}
