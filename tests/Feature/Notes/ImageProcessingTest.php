<?php

use Coleus\Notes\Markdown\NoteMarkdown;
use Coleus\Notes\Services\Attachments;
use Coleus\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    if (! function_exists('imagecreatetruecolor') || ! function_exists('exif_read_data')) {
        $this->markTestSkipped('Needs the gd and exif extensions.');
    }

    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/**
 * A JPEG with an EXIF block holding an orientation and a GPS tag, made by
 * hand since gd never writes EXIF itself.
 */
function jpegWithExif(int $width, int $height, int $orientation): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width - 1, (int) ($height / 2), imagecolorallocate($image, 255, 0, 0));
    ob_start();
    imagejpeg($image);
    $jpeg = (string) ob_get_clean();

    // TIFF, little endian: IFD0 = Orientation + pointer to a GPS IFD with GPSLatitudeRef "N".
    $ifd0 = pack('v', 2)
        .pack('vvVv', 0x0112, 3, 1, $orientation).pack('v', 0)
        .pack('vvVV', 0x8825, 4, 1, 38)
        .pack('V', 0);
    $gps = pack('v', 1).pack('vvVa4', 0x0001, 2, 2, "N\0\0\0").pack('V', 0);
    $tiff = 'II'.pack('vV', 42, 8).$ifd0.$gps;
    $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\0\0".$tiff;

    return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
}

it('turns photos upright, strips their metadata and makes smaller copies', function () {
    config(['notes.attachments.image_widths' => [100, 5000], 'notes.attachments.max_dimension' => 1000]);
    $original = jpegWithExif(300, 200, 6);
    expect(exif_read_data('data://image/jpeg;base64,'.base64_encode($original)))->toHaveKey('GPSLatitudeRef');

    $attachment = app(Attachments::class)->store(UploadedFile::fake()->createWithContent('photo.jpg', $original))->fresh();

    $cleaned = Storage::disk('local')->get($attachment->path);
    $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($cleaned));

    expect($attachment->processed_at)->not->toBeNull()
        ->and([$attachment->width, $attachment->height])->toBe([200, 300])
        ->and(is_array($exif) ? array_intersect_key($exif, ['GPSLatitudeRef' => 1, 'Orientation' => 1]) : [])->toBe([])
        ->and($attachment->variants)->toBe(['100' => $attachment->path.'-100.webp'])
        ->and(getimagesizefromstring(Storage::disk('local')->get($attachment->path.'-100.webp'))[0])->toBe(100);
});

it('shrinks very large images and serves a copy for ?w=', function () {
    config(['notes.attachments.image_widths' => [320], 'notes.attachments.max_dimension' => 500]);
    $image = imagecreatetruecolor(1200, 600);
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();

    $attachment = app(Attachments::class)->store(UploadedFile::fake()->createWithContent('wide.png', $png))->fresh();

    expect([$attachment->width, $attachment->height])->toBe([500, 250]);

    $this->get(route('notes.attachments.show', ['attachment' => $attachment->uuid, 'w' => 320]))->assertHeader('Content-Type', 'image/webp');
    $this->get(route('notes.attachments.show', ['attachment' => $attachment->uuid, 'w' => 999]))->assertHeader('Content-Type', 'image/png');
});

it('leaves images gd cannot read untouched, and marks them done', function () {
    $gif = "GIF89a\x01\0\x01\0\x80\0\0\0\0\0\xff\xff\xff!\xf9\x04\x01\0\0\0\0,\0\0\0\0\x01\0\x01\0\0\x02\x02D\x01\0;";

    $attachment = app(Attachments::class)->store(UploadedFile::fake()->createWithContent('anim.gif', $gif))->fresh();

    expect($attachment->processed_at)->not->toBeNull()
        ->and(Storage::disk('local')->get($attachment->path))->toBe($gif)
        ->and($attachment->variants)->toBeNull();
});

it('gives attachment images a srcset and every image lazy loading', function () {
    config(['notes.attachments.image_widths' => [320, 960]]);
    $uuid = '0b7b9c1e-1f2a-4c3d-9e8f-123456789abc';

    expect(NoteMarkdown::toHtml("![pic](/notes/attachments/{$uuid}/pic.jpg)"))
        ->toContain("srcset=\"/notes/attachments/{$uuid}/pic.jpg?w=320 320w, /notes/attachments/{$uuid}/pic.jpg?w=960 960w\"")
        ->toContain('loading="lazy"')
        ->and(NoteMarkdown::toHtml('![x](https://x.test/a.png)'))->toContain('loading="lazy"')->not->toContain('srcset');
});
