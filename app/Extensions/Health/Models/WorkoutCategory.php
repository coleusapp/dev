<?php

namespace App\Extensions\Health\Models;

use App\Packages\Core\Concerns\AutoAssignUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Extensions\Health\Models\CategoryExercise> $categoryExercises
 * @property-read int|null $category_exercises_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutCategory withoutTrashed()
 * @mixin \Eloquent
 */
class WorkoutCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use AutoAssignUser;

    public $fillable = ['name'];

    public function categoryExercises(): HasMany
    {
        return $this->hasMany(CategoryExercise::class);
    }
}
