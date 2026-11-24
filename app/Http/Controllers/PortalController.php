<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Notice;
use App\Models\PortalSetting;
use App\Models\Service;

class PortalController extends Controller
{
    public function index()
    {
        return view('portal', [
            'settings' => PortalSetting::current(),
            'services' => Service::ordered()->get(),
            'notices' => Notice::sorted()->get(),
            'bookmarks' => Bookmark::ordered()->get(),
        ]);
    }

    public function showWifi()
    {
        return view('wifi', [
            'settings' => PortalSetting::current(),
        ]);
    }

    public function pingServices()
    {
        $services = Service::pingAll();

        return response()->json([
            'success' => true,
            'services' => $services,
        ]);
    }

    public function pingService(Service $service)
    {
        $status = $service->ping();

        return response()->json([
            'success' => true,
            'id' => $service->id,
            'status' => $status,
        ]);
    }
}
