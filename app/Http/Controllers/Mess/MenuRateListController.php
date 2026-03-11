<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\MenuRateList;
use App\Models\Mess\Inventory;

class MenuRateListController extends Controller
{
    public function index()
    {
        $menus = MenuRateList::with('inventory')->paginate(20);
        return view('admin.mess.menu-rate-lists.index', compact('menus'));
    }

    public function create()
    {
        $items = Inventory::where('is_active', true)->get();
        return view('admin.mess.menu-rate-lists.create', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'menu_item_name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from'
        ]);

        MenuRateList::create($request->all());
        return redirect()->route('admin.mess.menu-rate-lists.index')->with('success', 'Menu rate created successfully.');
    }

    public function show($id)
    {
        $menu = MenuRateList::with('inventory')->findOrFail($id);
        return view('admin.mess.menu-rate-lists.show', compact('menu'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $menu = MenuRateList::findOrFail($id);
        $items = Inventory::where('is_active', true)->get();
        return view('admin.mess.menu-rate-lists.edit', compact('menu', 'items'));
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
        $menu = MenuRateList::findOrFail($id);
        
        $request->validate([
            'menu_item_name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from'
        ]);

        $menu->update($request->all());
        return redirect()->route('admin.mess.menu-rate-lists.index')->with('success', 'Menu rate updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $menu = MenuRateList::findOrFail($id);
        $menu->delete();
        return redirect()->route('admin.mess.menu-rate-lists.index')->with('success', 'Menu rate deleted successfully.');
    }
}
