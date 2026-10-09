<?php

namespace Database\Seeders\Concerns;

use App\Models\Listing;
use App\Models\ListingMedia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait SeedsListingPhotos
{
    /**
     * Attaches a small real-photo gallery to a demo listing so the gallery,
     * cover image, and card thumbnails aren't empty on a fresh seed. Photos
     * come from Lorem Picsum (real photography, seeded per listing/index so
     * a fresh seed always reproduces the same set) and fall back to a
     * generated gradient if the seeder is run offline. Skipped once a
     * listing already has media, matching the firstOrCreate idempotency
     * used by the seeders.
     */
    private function attachGalleryPhotos(Listing $listing): void
    {
        if ($listing->media()->exists()) {
            return;
        }

        $fallbackPalettes = [
            [[22, 40, 31], [168, 127, 46]],   // ink -> gold
            [[124, 46, 59], [201, 162, 79]],  // wine -> gold-bright
            [[31, 58, 44], [111, 132, 101]],  // ink-2 -> sage
            [[239, 234, 219], [124, 46, 59]], // paper-3 -> wine
        ];

        foreach ($fallbackPalettes as $index => $palette) {
            $path = "listings/{$listing->id}/".Str::random(12).'.jpg';
            $seed = "invita-listing-{$listing->id}-{$index}";

            $contents = $this->fetchStockPhoto($seed) ?? $this->placeholderPhoto($palette);

            Storage::disk('public')->put($path, $contents);

            ListingMedia::create([
                'listing_id' => $listing->id,
                'type' => 'photo',
                'path' => $path,
                'is_cover' => $index === 0,
                'position' => $index,
            ]);
        }
    }

    /**
     * Fetches a real photo from Lorem Picsum keyed by a stable seed, so
     * reseeding from scratch always produces the same image per slot.
     * Returns null (letting the caller fall back to a placeholder) if the
     * request fails, so seeding still works without network access.
     */
    private function fetchStockPhoto(string $seed): ?string
    {
        try {
            $response = Http::timeout(10)->get("https://picsum.photos/seed/{$seed}/1200/800");

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Renders a vertical-gradient JPEG in-process with GD so demo photos
     * still work without network access to fetch stock images.
     */
    private function placeholderPhoto(array $palette): string
    {
        [$from, $to] = $palette;
        [$width, $height] = [1200, 800];

        $image = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $color = imagecolorallocate(
                $image,
                (int) ($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) ($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) ($from[2] + ($to[2] - $from[2]) * $ratio),
            );
            imageline($image, 0, $y, $width, $y, $color);
        }

        ob_start();
        imagejpeg($image, null, 85);
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
