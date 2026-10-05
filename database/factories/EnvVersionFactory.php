<?php

namespace Database\Factories;

use App\Models\EnvVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnvVersion>
 */
class EnvVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'path' => '/home/vito/'.$this->faker->domainName().'/.env',
            'content' => "APP_ENV=production\nAPP_DEBUG=false",
        ];
    }
}
