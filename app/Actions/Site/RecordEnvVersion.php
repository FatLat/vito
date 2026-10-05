<?php

namespace App\Actions\Site;

use App\Models\EnvVersion;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordEnvVersion
{
    /**
     * Keep the content a .env file had before it was overwritten, pruning the
     * oldest copies so each site and path keeps at most EnvVersion::KEEP.
     */
    public function record(Site $site, string $path, string $previousContent, ?User $user): void
    {
        if (trim($previousContent) === '') {
            return;
        }

        DB::transaction(function () use ($site, $path, $previousContent, $user): void {
            $this->store($site, $path, $previousContent, $user);
        });
    }

    private function store(Site $site, string $path, string $previousContent, ?User $user): void
    {
        EnvVersion::query()->create([
            'site_id' => $site->id,
            'user_id' => $user?->id,
            'path' => $path,
            'content' => $previousContent,
        ]);

        $keepFromId = EnvVersion::query()
            ->where('site_id', $site->id)
            ->where('path', $path)
            ->orderByDesc('id')
            ->skip(EnvVersion::KEEP - 1)
            ->value('id');

        if ($keepFromId !== null) {
            EnvVersion::query()
                ->where('site_id', $site->id)
                ->where('path', $path)
                ->where('id', '<', $keepFromId)
                ->delete();
        }
    }
}
