<?php

use Coleus\Notes\Services\Attachments;
use Coleus\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('reads a video and grabs its poster frame', function () {
    $probe = Process::run(['ffmpeg', '-version']);

    if (! $probe->successful()) {
        $this->markTestSkipped('Needs FFmpeg.');
    }

    $file = tempnam(sys_get_temp_dir(), 'clip').'.mp4';
    Process::run(['ffmpeg', '-v', 'error', '-y', '-f', 'lavfi', '-i', 'testsrc=duration=3:size=320x240:rate=10', '-pix_fmt', 'yuv420p', $file])->throw();

    $attachment = app(Attachments::class)->store(new UploadedFile($file, 'clip.mp4', 'video/mp4', null, true))->fresh();
    @unlink($file);

    expect($attachment->kind->value)->toBe('video')
        ->and([$attachment->width, $attachment->height])->toBe([320, 240])
        ->and($attachment->duration_ms)->toBeGreaterThanOrEqual(2900)->toBeLessThanOrEqual(3100)
        ->and($attachment->poster_path)->toBe($attachment->path.'-poster.jpg')
        ->and($attachment->processed_at)->not->toBeNull();

    $this->get(route('notes.attachments.show', ['attachment' => $attachment->uuid, 'poster' => 1]))
        ->assertOk()->assertHeader('Content-Type', 'image/jpeg');
});

it('leaves a video as it is when FFmpeg is not there', function () {
    if (! Process::run(['ffmpeg', '-version'])->successful()) {
        $this->markTestSkipped('Needs FFmpeg to make the test video.');
    }

    $file = tempnam(sys_get_temp_dir(), 'clip').'.mp4';
    Process::run(['ffmpeg', '-v', 'error', '-y', '-f', 'lavfi', '-i', 'testsrc=duration=1:size=64x48:rate=5', '-pix_fmt', 'yuv420p', $file])->throw();
    config(['notes.attachments.ffprobe' => 'definitely-not-ffprobe', 'notes.attachments.ffmpeg' => 'definitely-not-ffmpeg']);

    $attachment = app(Attachments::class)->store(new UploadedFile($file, 'clip.mp4', 'video/mp4', null, true))->fresh();
    @unlink($file);

    expect($attachment->processed_at)->not->toBeNull()
        ->and($attachment->poster_path)->toBeNull()
        ->and($attachment->duration_ms)->toBeNull();

    $this->get(route('notes.attachments.show', ['attachment' => $attachment->uuid, 'poster' => 1]))->assertNotFound();
});
