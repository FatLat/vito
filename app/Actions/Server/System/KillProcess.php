<?php

namespace App\Actions\Server\System;

use App\Exceptions\SSHError;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class KillProcess
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws SSHError
     */
    public function kill(Server $server, array $input): void
    {
        $validated = Validator::make($input, [
            'pid' => ['required', 'integer', 'min:2'],
            'signal' => ['required', 'in:TERM,KILL'],
        ])->validate();

        $server->system()->kill((int) $validated['pid'], $validated['signal']);
    }
}
