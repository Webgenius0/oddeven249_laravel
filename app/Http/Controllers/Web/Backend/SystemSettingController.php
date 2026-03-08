<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index()
    {

        $setting = SystemSetting::latest('id')->first();
        return view('backend.layouts.settings.system_settings', compact('setting'));
    }

    /**
     * Update the system settings.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'nullable|string',
            'email'          => 'required|email',
            'system_name'    => 'nullable|string',
            'copyright_text' => 'nullable|string',
            'logo'           => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'favicon'        => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'description'    => 'nullable|string',
            'platform_commission' => 'required|numeric|min:0|max:100',
            'tax_rate'            => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $data = SystemSetting::first();
        try {
            $setting                 = SystemSetting::firstOrNew();
            $setting->title          = $request->title;
            $setting->email          = $request->email;
            $setting->system_name    = $request->system_name;
            $setting->copyright_text = $request->copyright_text;
            $setting->logo           = $request->logo;
            $setting->favicon        = $request->favicon;
            $setting->description    = $request->description;
            $setting->platform_commission = $request->platform_commission;
            $setting->tax_rate            = $request->tax_rate;

            if ($request->hasFile('logo')) {
                $setting->logo = uploadImage($request->file('logo'), 'logos');

                if ($data->logo) {
                    $previousImagePath = public_path($data->logo);
                    if (file_exists($previousImagePath)) {
                        unlink($previousImagePath);
                    }
                }
            } else {
                $setting->logo = $data->logo;
            }

            if ($request->hasFile('favicon')) {
                $setting->favicon = uploadImage($request->file('favicon'), 'favicons');

                if ($data->favicon) {
                    $previousImagePath = public_path($data->favicon);
                    if (file_exists($previousImagePath)) {
                        unlink($previousImagePath);
                    }
                }
            } else {
                $setting->favicon = $data->favicon;
            }

            $setting->save();

            \Illuminate\Support\Facades\Cache::forget('platform_commission_rate');
            \Illuminate\Support\Facades\Cache::forget('platform_tax_rate');

            return back()->with('t-success', 'Updated successfully');
        } catch (Exception) {
            return back()->with('t-error', 'Failed to update');
        }
    }
}
