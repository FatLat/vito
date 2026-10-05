<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EnvVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A copy of a site's .env file taken right before it was overwritten from Vito.
 *
 * @property int $site_id
 * @property ?int $user_id
 * @property string $path
 * @property string $content
 * @property Carbon $created_at
 * @property Site $site
 * @property ?User $user
 */
class EnvVersion extends AbstractModel
{
    /** @use HasFactory<EnvVersionFactory> */
    use HasFactory;

    public const int KEEP = 20;

    protected $fillable = [
        'site_id',
        'user_id',
        'path',
        'content',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'user_id' => 'integer',
        'content' => 'encrypted',
    ];

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
