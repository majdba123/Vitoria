<?php

namespace App\Services\Admin;

use App\Models\Syndicate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyndicateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Syndicate
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone'],
                'national_id' => fake()->unique()->numerify('##########'),
                'age' => 30,
                'membership_number' => fake()->unique()->bothify('SYN-########'),
                'type' => User::TYPE_SYNDICATE,
                'password' => $data['password'],
            ]);

            $logo = ($data['logo'] ?? null) instanceof UploadedFile
                ? $data['logo']->store('syndicates', 'public')
                : null;

            $syndicate = Syndicate::query()->create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'status' => $data['status'] ?? Syndicate::STATUS_ACTIVE,
                'logo' => $logo,
            ]);

            $this->flushCaches();

            return $syndicate->fresh(['user']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Syndicate $syndicate, array $data): Syndicate
    {
        return DB::transaction(function () use ($syndicate, $data) {
            $userFields = array_filter(
                array_intersect_key($data, array_flip(['name', 'email', 'password'])),
                fn ($value) => $value !== null && $value !== '',
            );

            if (array_key_exists('phone', $data)) {
                $userFields['phone_number'] = $data['phone'];
            }

            if ($userFields) {
                $syndicate->user->update($userFields);
            }

            $syndicateFields = array_intersect_key($data, array_flip(['name', 'type', 'phone', 'email', 'status']));

            if (($data['logo'] ?? null) instanceof UploadedFile) {
                if ($syndicate->logo) {
                    Storage::disk('public')->delete($syndicate->logo);
                }

                $syndicateFields['logo'] = $data['logo']->store('syndicates', 'public');
            }

            if ($syndicateFields) {
                $syndicate->update($syndicateFields);
            }

            $this->flushCaches();

            return $syndicate->fresh(['user']);
        });
    }

    public function toggleActive(Syndicate $syndicate): Syndicate
    {
        $syndicate->update([
            'status' => $syndicate->status === Syndicate::STATUS_ACTIVE
                ? Syndicate::STATUS_INACTIVE
                : Syndicate::STATUS_ACTIVE,
        ]);

        $this->flushCaches();

        return $syndicate->fresh(['user']);
    }

    public function delete(Syndicate $syndicate): void
    {
        DB::transaction(function () use ($syndicate) {
            if ($syndicate->logo) {
                Storage::disk('public')->delete($syndicate->logo);
            }

            $user = $syndicate->user;
            $user?->tokens()->delete();
            $syndicate->delete();
            $user?->delete();

            $this->flushCaches();
        });
    }

    protected function flushCaches(): void
    {
        Cache::forget('admin_dashboard_overview');

        try {
            Cache::tags(['syndicates'])->flush();
        } catch (\Exception) {
            //
        }
    }
}
