<?php

namespace Coleus\Music\Models;

use Coleus\Users\Models\UserAlias;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Coleus\Music\Models\Weight> $weights
 * @property-read int|null $weights_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MusicUser withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 * @mixin \Coleus\Users\Models\User
 */
class MusicUser extends UserAlias
{
    protected $table = 'users';
}
