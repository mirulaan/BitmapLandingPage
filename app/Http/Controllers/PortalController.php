<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Notice;
use App\Models\PortalSetting;
use App\Models\Service;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function index()
    {
        return view('portal', [
            'settings'  => PortalSetting::current(),
            'services'  => Service::ordered()->get(),
            'notices'   => Notice::sorted()->get(),
            'bookmarks' => Bookmark::ordered()->get(),
        ]);
    }
}
