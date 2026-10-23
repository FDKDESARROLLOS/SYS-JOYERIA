<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.store', [
            'storeSetting' => StoreSetting::query()->first() ?? new StoreSetting,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $storeSetting = StoreSetting::query()->first() ?? new StoreSetting;
        $storeSetting->fill($validated);
        $storeSetting->save();

        return redirect()
            ->route('settings.store')
            ->with('status', 'Los datos de la tienda se actualizaron correctamente.');
    }
}
