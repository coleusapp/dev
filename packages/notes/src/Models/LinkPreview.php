<?php

namespace Coleus\Notes\Models;

use Coleus\Notes\NotesModelDefaults;

/**
 * A cached link preview (A4.1): what a public web page says about itself.
 * Shared by everyone (it's the same page for all), keyed by the URL's
 * hash; failures are cached too, for less time.
 *
 * @property int $id
 * @property string $url_hash
 * @property string $url
 * @property string|null $title
 * @property string|null $description
 * @property string|null $image
 * @property string|null $site_name
 * @property bool $failed
 * @property \Illuminate\Support\Carbon $fetched_at
 *
 * @mixin \Eloquent
 */
class LinkPreview extends NotesModelDefaults
{
    protected $fillable = [
        'url_hash',
        'url',
        'title',
        'description',
        'image',
        'site_name',
        'failed',
        'fetched_at',
    ];

    public function casts(): array
    {
        return [
            'failed' => 'boolean',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * @return array{url: string, title: string|null, description: string|null, image: string|null, site_name: string|null}|null
     */
    public function toClient(): ?array
    {
        return $this->failed ? null : [
            'url' => $this->url,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'site_name' => $this->site_name,
        ];
    }
}
