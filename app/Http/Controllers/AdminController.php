<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Notice;
use App\Models\PortalSetting;
use App\Models\Service;
use Database\Seeders\StaffHubSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin', [
            'settings' => PortalSetting::current(),
            'services' => Service::ordered()->get(),
            'notices' => Notice::sorted()->get(),
            'bookmarks' => Bookmark::ordered()->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'office_name' => 'required|string|max:60',
            'tagline' => 'nullable|string|max:90',
            'helpdesk_email' => 'required|email|max:120',
            'helpdesk_ext' => 'nullable|string|max:12',
            'wifi_ssid' => 'nullable|string|max:64',
            'wifi_password' => 'nullable|string|max:64',
            'wifi_encryption' => 'nullable|string|in:WPA,WEP,nopass',
            'theme' => 'nullable|in:day,night',
            'wallpaper_enabled' => 'sometimes|boolean',
            'wallpaper_mode' => 'required|in:picsum,custom',
            'wallpaper_custom_urls' => 'nullable',
            'wallpaper_interval' => 'required|integer|min:0',
            'wallpaper_blur' => 'required|integer|min:0|max:60',
            'wallpaper_veil' => 'required|integer|min:0|max:90',
        ]);

        $setting = PortalSetting::current();

        if (isset($data['wallpaper_custom_urls'])) {
            if (is_string($data['wallpaper_custom_urls'])) {
                $lines = array_map('trim', explode("\n", $data['wallpaper_custom_urls']));
                $data['wallpaper_custom_urls'] = array_values(array_filter($lines));
            } elseif (! is_array($data['wallpaper_custom_urls'])) {
                $data['wallpaper_custom_urls'] = [];
            }
        }

        $data['wallpaper_enabled'] = $request->boolean('wallpaper_enabled');

        $setting->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'settings' => $setting]);
        }

        return back()->with('status', 'Settings updated successfully.');
    }

    public function storeService(Request $request)
    {
        $data = $request->validate([
            'icon' => 'nullable|string|max:16',
            'name' => 'required|string|max:40',
            'description' => 'nullable|string|max:90',
            'url' => 'required|url|max:255',
            'status' => 'nullable|in:online,offline',
        ]);

        $maxOrder = Service::max('sort_order') ?? 0;
        $data['sort_order'] = $maxOrder + 1;
        $data['icon'] = $data['icon'] ?: '🔧';

        if (empty($data['status'])) {
            $data['status'] = Service::pingUrl($data['url']);
        }

        $service = Service::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'service' => $service], 201);
        }

        return back()->with('status', 'Service added.');
    }

    public function updateService(Request $request, Service $service)
    {
        $data = $request->validate([
            'icon' => 'nullable|string|max:16',
            'name' => 'required|string|max:40',
            'description' => 'nullable|string|max:90',
            'url' => 'required|url|max:255',
            'status' => 'nullable|in:online,offline',
            'sort_order' => 'nullable|integer',
        ]);

        $data['icon'] = $data['icon'] ?: '🔧';

        if (empty($data['status']) && isset($data['url'])) {
            $data['status'] = Service::pingUrl($data['url']);
        }

        $service->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'service' => $service]);
        }

        return back()->with('status', 'Service updated.');
    }

    public function destroyService(Request $request, Service $service)
    {
        $service->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Service removed.');
    }

    public function reorderServices(Request $request)
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:services,id',
        ]);

        foreach ($data['order'] as $index => $id) {
            Service::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Services reordered.');
    }

    public function storeNotice(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:80',
            'body' => 'nullable|string|max:400',
            'category' => 'required|in:general,it,reminder',
            'priority' => 'required|in:normal,high',
            'pinned' => 'sometimes|boolean',
        ]);

        $data['pinned'] = $request->boolean('pinned');
        $notice = Notice::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'notice' => $notice], 201);
        }

        return back()->with('status', 'Notice published.');
    }

    public function updateNotice(Request $request, Notice $notice)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:80',
            'body' => 'nullable|string|max:400',
            'category' => 'sometimes|required|in:general,it,reminder',
            'priority' => 'sometimes|required|in:normal,high',
            'pinned' => 'sometimes|boolean',
        ]);

        if ($request->has('pinned')) {
            $data['pinned'] = $request->boolean('pinned');
        }

        $notice->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'notice' => $notice]);
        }

        return back()->with('status', 'Notice updated.');
    }

    public function destroyNotice(Request $request, Notice $notice)
    {
        $notice->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Notice deleted.');
    }

    public function storeBookmark(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:40',
            'url' => 'required|url|max:255',
        ]);

        $maxOrder = Bookmark::max('sort_order') ?? 0;
        $data['sort_order'] = $maxOrder + 1;

        $bookmark = Bookmark::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'bookmark' => $bookmark], 201);
        }

        return back()->with('status', 'Bookmark added.');
    }

    public function updateBookmark(Request $request, Bookmark $bookmark)
    {
        $data = $request->validate([
            'label' => 'required|string|max:40',
            'url' => 'required|url|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $bookmark->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'bookmark' => $bookmark]);
        }

        return back()->with('status', 'Bookmark updated.');
    }

    public function destroyBookmark(Request $request, Bookmark $bookmark)
    {
        $bookmark->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Bookmark removed.');
    }

    public function exportConfig()
    {
        $setting = PortalSetting::current();
        $services = Service::ordered()->get()->map(fn ($s) => [
            'id' => (string) $s->id,
            'icon' => $s->icon,
            'name' => $s->name,
            'desc' => $s->description,
            'url' => $s->url,
            'status' => $s->status,
        ]);

        $notices = Notice::sorted()->get()->map(fn ($n) => [
            'id' => (string) $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'category' => $n->category,
            'priority' => $n->priority,
            'pinned' => (bool) $n->pinned,
            'ts' => $n->created_at ? $n->created_at->getTimestampMs() : now()->getTimestampMs(),
        ]);

        $bookmarks = Bookmark::ordered()->get()->map(fn ($b) => [
            'id' => (string) $b->id,
            'label' => $b->label,
            'url' => $b->url,
        ]);

        $payload = [
            'version' => 1,
            'office' => $setting->office_name,
            'tagline' => $setting->tagline,
            'helpdesk' => [
                'email' => $setting->helpdesk_email,
                'ext' => $setting->helpdesk_ext,
            ],
            'wifi' => [
                'ssid' => $setting->wifi_ssid,
                'password' => $setting->wifi_password,
                'encryption' => $setting->wifi_encryption,
            ],
            'theme' => $setting->theme,
            'wallpaper' => [
                'enabled' => (bool) $setting->wallpaper_enabled,
                'mode' => $setting->wallpaper_mode,
                'customUrls' => $setting->wallpaper_custom_urls ?? [],
                'interval' => $setting->wallpaper_interval,
                'blur' => $setting->wallpaper_blur,
                'veil' => $setting->wallpaper_veil,
            ],
            'services' => $services,
            'notices' => $notices,
            'bookmarks' => $bookmarks,
        ];

        $filename = 'intranet-portal-'.date('Y-m-d').'.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function importConfig(Request $request)
    {
        $request->validate([
            'config_file' => 'required|file',
        ]);

        $content = file_get_contents($request->file('config_file')->getRealPath());
        $data = json_decode($content, true);

        if (! is_array($data)) {
            return back()->withErrors(['config_file' => 'Invalid JSON config file.']);
        }

        DB::transaction(function () use ($data) {
            $setting = PortalSetting::current();
            $wp = $data['wallpaper'] ?? [];
            $hd = $data['helpdesk'] ?? [];
            $wifi = $data['wifi'] ?? [];

            $setting->update([
                'office_name' => $data['office'] ?? $data['office_name'] ?? $setting->office_name,
                'tagline' => $data['tagline'] ?? $setting->tagline,
                'helpdesk_email' => $hd['email'] ?? $data['helpdesk_email'] ?? $setting->helpdesk_email,
                'helpdesk_ext' => $hd['ext'] ?? $data['helpdesk_ext'] ?? $setting->helpdesk_ext,
                'wifi_ssid' => $wifi['ssid'] ?? $data['wifi_ssid'] ?? $setting->wifi_ssid,
                'wifi_password' => $wifi['password'] ?? $data['wifi_password'] ?? $setting->wifi_password,
                'wifi_encryption' => $wifi['encryption'] ?? $data['wifi_encryption'] ?? $setting->wifi_encryption,
                'theme' => $data['theme'] ?? $setting->theme,
                'wallpaper_enabled' => isset($wp['enabled']) ? (bool) $wp['enabled'] : $setting->wallpaper_enabled,
                'wallpaper_mode' => $wp['mode'] ?? $setting->wallpaper_mode,
                'wallpaper_custom_urls' => $wp['customUrls'] ?? $wp['wallpaper_custom_urls'] ?? $setting->wallpaper_custom_urls,
                'wallpaper_interval' => $wp['interval'] ?? $setting->wallpaper_interval,
                'wallpaper_blur' => $wp['blur'] ?? $setting->wallpaper_blur,
                'wallpaper_veil' => $wp['veil'] ?? $setting->wallpaper_veil,
            ]);

            if (isset($data['services']) && is_array($data['services'])) {
                Service::truncate();
                foreach ($data['services'] as $idx => $s) {
                    Service::create([
                        'icon' => $s['icon'] ?? '🔧',
                        'name' => $s['name'] ?? 'Service',
                        'description' => $s['desc'] ?? $s['description'] ?? '',
                        'url' => $s['url'] ?? '#',
                        'status' => in_array($s['status'] ?? '', ['online', 'offline']) ? $s['status'] : Service::pingUrl($s['url'] ?? ''),
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            if (isset($data['notices']) && is_array($data['notices'])) {
                Notice::truncate();
                foreach ($data['notices'] as $n) {
                    $createdAt = isset($n['ts']) ? now()->setTimestamp((int) ($n['ts'] / 1000)) : now();
                    Notice::create([
                        'title' => $n['title'] ?? 'Notice',
                        'body' => $n['body'] ?? '',
                        'category' => in_array($n['category'] ?? '', ['general', 'it', 'reminder']) ? $n['category'] : 'general',
                        'priority' => in_array($n['priority'] ?? '', ['normal', 'high']) ? $n['priority'] : 'normal',
                        'pinned' => ! empty($n['pinned']),
                        'created_at' => $createdAt,
                    ]);
                }
            }

            if (isset($data['bookmarks']) && is_array($data['bookmarks'])) {
                Bookmark::truncate();
                foreach ($data['bookmarks'] as $idx => $b) {
                    Bookmark::create([
                        'label' => $b['label'] ?? 'Bookmark',
                        'url' => $b['url'] ?? '#',
                        'sort_order' => $idx + 1,
                    ]);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Configuration imported successfully.');
    }

    public function resetDefaults(Request $request)
    {
        $seeder = new StaffHubSeeder;
        $seeder->run();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('status', 'Portal reset to default settings.');
    }
}
