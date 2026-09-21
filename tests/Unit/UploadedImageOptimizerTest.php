<?php

namespace Tests\Unit;

use App\Services\UploadedImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadedImageOptimizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('Extension GD absente : le service stocke alors les fichiers tels quels.');
        }
    }

    private function makeImage(int $width, int $height, string $format): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $blue = imagecolorallocate($image, 20, 78, 140);
        $orange = imagecolorallocate($image, 245, 130, 31);
        imagefilledrectangle($image, 0, 0, $width, $height, $blue);
        imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), (int) ($width / 2), (int) ($height / 2), $orange);

        $path = tempnam(sys_get_temp_dir(), 'aj-img-') . '.' . $format;
        $format === 'png' ? imagepng($image, $path) : imagejpeg($image, $path, 95);
        imagedestroy($image);

        return $path;
    }

    public function test_large_jpeg_is_resized_and_lighter(): void
    {
        $source = $this->makeImage(4000, 2250, 'jpg');
        $before = filesize($source);

        [$binary, $extension] = (new UploadedImageOptimizer())->encode($source, 1600);
        $info = getimagesizefromstring($binary);

        $this->assertSame(1600, $info[0]);
        $this->assertSame(900, $info[1]);
        $this->assertContains($extension, ['webp', 'jpg']);
        $this->assertLessThan($before, strlen($binary));
        @unlink($source);
    }

    public function test_small_image_keeps_its_dimensions(): void
    {
        $source = $this->makeImage(640, 480, 'png');

        [$binary, $extension] = (new UploadedImageOptimizer())->encode($source, 1600);
        $info = getimagesizefromstring($binary);

        $this->assertSame([640, 480], [$info[0], $info[1]]);
        $this->assertContains($extension, ['webp', 'png']);
        @unlink($source);
    }

    public function test_non_raster_file_is_left_untouched(): void
    {
        $svg = tempnam(sys_get_temp_dir(), 'aj-svg-') . '.svg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

        $this->assertNull((new UploadedImageOptimizer())->encode($svg, 1600));
        @unlink($svg);
    }

    public function test_store_uploaded_writes_optimized_file_to_public_disk(): void
    {
        Storage::fake('public');
        $source = $this->makeImage(3000, 2000, 'jpg');
        $upload = new UploadedFile($source, 'photo.jpg', 'image/jpeg', null, true);

        $path = (new UploadedImageOptimizer())->storeUploaded($upload, 'front/home/hero', 1920);

        $this->assertNotNull($path);
        $this->assertStringStartsWith('front/home/hero/', $path);
        Storage::disk('public')->assertExists($path);
        $info = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame(1920, $info[0]);
        @unlink($source);
    }

    public function test_optimize_stored_returns_new_path_and_keeps_original(): void
    {
        Storage::fake('public');
        $source = $this->makeImage(3200, 1800, 'jpg');
        Storage::disk('public')->put('front/home/x/big.jpg', file_get_contents($source));

        $newPath = (new UploadedImageOptimizer())->optimizeStored('front/home/x/big.jpg', 1600);

        $this->assertNotNull($newPath);
        $this->assertNotSame('front/home/x/big.jpg', $newPath);
        Storage::disk('public')->assertExists('front/home/x/big.jpg');
        Storage::disk('public')->assertExists($newPath);
        $this->assertLessThan(Storage::disk('public')->size('front/home/x/big.jpg'), Storage::disk('public')->size($newPath));
        @unlink($source);
    }
}
