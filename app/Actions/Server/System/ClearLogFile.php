<?php

namespace App\Actions\Server\System;

use App\Exceptions\SSHError;
use App\Models\Server;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClearLogFile
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws SSHError
     */
    public function clear(Server $server, array $input): void
    {
        $validated = Validator::make($input, [
            'path' => ['required', 'string', 'max:255', 'regex:/^\/var\/log\/[A-Za-z0-9._\/-]+\z/', 'not_regex:/(^|\/)\.\.(\/|$)/'],
        ])->validate();

        $server->system()->clearLog($validated['path']);
    }
}
