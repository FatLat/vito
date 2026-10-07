<?php

namespace App\Actions\Server\System;

use App\Exceptions\SSHError;
use App\Models\Server;
use App\SSH\OS\System;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
            'pid' => ['required', 'integer', 'min:2', 'max:4194304'],
            'signal' => ['required', Rule::in(System::SIGNALS)],
        ])->validate();

        $server->system()->kill((int) $validated['pid'], $validated['signal']);
    }
}
