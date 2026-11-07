<?php

namespace Coleus\Frame\Services;

use Coleus\Frame\Data\FrameData;
use Coleus\Frame\Facades\Frames;
use Coleus\Frame\Models\Frame;
use Coleus\Frame\Support\KioskToken;
use Coleus\Support\Services\Service;
use Coleus\Users\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** @extends Service<Frame, FrameData> */
class FrameService extends Service
{
    protected $model = Frame::class;

    protected $data = FrameData::class;

    /** @return Builder<Frame> */
    public function defaultQuery(): Builder
    {
        return Frame::query()->orderBy('name');
    }

    /** @return Collection<int, Frame> */
    public function list(): Collection
    {
        return $this->defaultQuery()
            ->withCount('artworks')
            ->get();
    }

    public function show(Frame $frame): Frame
    {
        return $frame->loadCount('artworks');
    }

    public function store(mixed $payload): Frame
    {
        return $this->create($payload)->frame;
    }

    public function create(mixed $payload): KioskToken
    {
        $frame = new Frame(FrameData::from($payload)->toArray());

        $token = $this->assignToken($frame);

        $frame->save();

        return new KioskToken($frame, $token);
    }

    public function regenerateToken(Frame $frame): KioskToken
    {
        $token = $this->assignToken($frame);

        $frame->save();

        return new KioskToken($frame, $token);
    }

    public function destroy(mixed $model): ?bool
    {
        $deleted = $model->delete();

        Frames::artwork()->deleteFiles($model);

        return $deleted;
    }

    public function fetchable(?int $frameId): Collection
    {
        return Frame::query()
            ->withoutGlobalScope(UserScope::class)
            ->when(
                $frameId,
                fn (Builder $query) => $query->whereKey($frameId),
                fn (Builder $query) => $query->where('is_active', true),
            )
            ->get();
    }

    private function assignToken(Frame $frame): string
    {
        $token = Frames::kiosk()->generateToken();

        $frame->token_hash = Frames::kiosk()->hashToken($token);

        return $token;
    }
}
