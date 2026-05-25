<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginCarouselImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LoginCarouselImageController extends Controller
{
    public function index()
    {
        $this->authorizeManager();

        $images = LoginCarouselImage::query()
            ->select(['id', 'image_path', 'sort_order', 'active_inactive'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.login-carousel-images.index', compact('images'));
    }

    public function store(Request $request)
    {
        $this->authorizeManager();
        $this->ensureTableExists();

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $nextOrder = (int) (LoginCarouselImage::query()->max('sort_order') ?? 0);
        $pk = Auth::user()?->pk;

        foreach ($validated['images'] as $image) {
            $nextOrder++;
            $path = $image->store('login-carousel-images', 'public');
            LoginCarouselImage::generateThumbnail($path);

            LoginCarouselImage::create([
                'image_path' => $path,
                'sort_order' => $nextOrder,
                'active_inactive' => true,
                'created_by_pk' => $pk,
                'updated_by_pk' => $pk,
            ]);
        }

        return redirect()->route('admin.login-carousel-images.index')
            ->with('success', 'Login carousel image(s) uploaded successfully.');
    }

    public function update(Request $request, LoginCarouselImage $loginCarouselImage)
    {
        $this->authorizeManager();
        $this->ensureTableExists();

        $validated = $request->validate([
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'active_inactive' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $loginCarouselImage->deleteStoredFiles();
            $validated['image_path'] = $request->file('image')->store('login-carousel-images', 'public');
            LoginCarouselImage::generateThumbnail($validated['image_path']);
        }

        unset($validated['image']);
        $validated['active_inactive'] = $request->boolean('active_inactive');
        $validated['updated_by_pk'] = Auth::user()?->pk;

        $loginCarouselImage->update($validated);

        return redirect()->route('admin.login-carousel-images.index')
            ->with('success', 'Login carousel image updated successfully.');
    }

    public function destroy(LoginCarouselImage $loginCarouselImage)
    {
        $this->authorizeManager();
        $this->ensureTableExists();

        $loginCarouselImage->deleteStoredFiles();
        $loginCarouselImage->delete();

        return redirect()->route('admin.login-carousel-images.index')
            ->with('success', 'Login carousel image deleted successfully.');
    }

    protected function authorizeManager(): void
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);
    }

    protected function ensureTableExists(): void
    {
        if (LoginCarouselImage::tableExists()) {
            return;
        }

        abort(503, 'Login carousel is not set up yet. Run: php artisan migrate --path=database/migrations/2026_05_21_000001_create_login_carousel_images_table.php');
    }
}
