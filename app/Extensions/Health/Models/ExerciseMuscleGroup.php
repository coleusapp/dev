<?php

namespace App\Extensions\Health\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $exercise_id
 * @property int $muscle_group_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Extensions\Health\Models\Exercise $exercise
 * @property-read \App\Extensions\Health\Models\MuscleGroup $muscleGroup
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseMuscleGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseMuscleGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseMuscleGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseMuscleGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseMuscleGroup whereExerciseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseMuscleGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseMuscleGroup whereMuscleGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseMuscleGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ExerciseMuscleGroup extends Model
{
    public $incrementing = true;

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }
}
