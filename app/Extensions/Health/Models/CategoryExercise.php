<?php

namespace App\Extensions\Health\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property int $category_id
 * @property int $exercise_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Extensions\Health\Models\Category $category
 * @property-read \App\Extensions\Health\Models\Exercise $exercise
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise whereExerciseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoryExercise withoutTrashed()
 * @mixin \Eloquent
 */
class CategoryExercise extends Pivot
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = true;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
