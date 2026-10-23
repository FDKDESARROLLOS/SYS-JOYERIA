<?php

namespace App\Http\Controllers;

use App\Models\ShippingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShippingSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.shipping', [
            'shippingSetting' => ShippingSetting::query()->first() ?? new ShippingSetting([
                'enabled' => true,
                'free_shipping_enabled' => false,
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'method_name' => ['required', 'string', 'max:255'],
            'estimated_time' => ['nullable', 'string', 'max:255'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'free_shipping_enabled' => ['required', 'boolean'],
            'free_shipping_type' => [
                'nullable',
                'required_if:free_shipping_enabled,1',
                Rule::in(['all', 'minimum']),
            ],
            'free_shipping_minimum' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(
                    fn (): bool => $request->boolean('free_shipping_enabled')
                        && $request->input('free_shipping_type') === 'minimum'
                ),
            ],
        ]);

        $validated['enabled'] = (bool) $validated['enabled'];
        $validated['free_shipping_enabled'] = (bool) $validated['free_shipping_enabled'];

        if (! $validated['free_shipping_enabled'] || $validated['free_shipping_type'] === 'all') {
            $validated['free_shipping_type'] = $validated['free_shipping_enabled']
                ? $validated['free_shipping_type']
                : null;
            $validated['free_shipping_minimum'] = null;
        }

        $shippingSetting = ShippingSetting::query()->first() ?? new ShippingSetting;
        $shippingSetting->fill($validated);
        $shippingSetting->save();

        return redirect()
            ->route('settings.shipping')
            ->with('status', 'La configuración de envío se actualizó correctamente.');
    }
}
