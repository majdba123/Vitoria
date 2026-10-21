<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * The only way to create or update a Ministry account: no admin screen can
 * grant the type, and no default credentials exist. Re-running it for the
 * same phone updates that Ministry account instead of creating a second one.
 */
class ProvisionMinistryAccount extends Command
{
    /**
     * @var string
     */
    protected $signature = 'ministry:provision
        {--phone= : Phone number the Ministry account signs in with}
        {--name= : Display name}
        {--email= : Optional email address}
        {--national-id= : Official identifier of the account holder (required for a new account)}
        {--generate-password : Generate a strong password and print it once}
        {--reset-password : Replace the password of an existing Ministry account}';

    /**
     * @var string
     */
    protected $description = 'Create or update the read-only Ministry account (agriculture + veterinary oversight)';

    public function handle(AuditLogService $auditLog): int
    {
        $interactive = $this->input->isInteractive();
        $phone = $this->option('phone') ?: ($interactive ? text('Phone number', required: true) : null);

        if (! $phone) {
            $this->components->error('A phone number is required (--phone).');

            return self::FAILURE;
        }

        $existing = User::query()->where('phone_number', $phone)->first();

        if ($existing && ! $existing->isMinistry()) {
            $this->components->error('This phone number belongs to another account. Ministry access is never added to an existing non-Ministry account.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: ($existing?->name ?? ($interactive ? text('Display name', required: true) : null));
        $email = $this->option('email') ?: $existing?->email;
        $nationalId = $this->option('national-id') ?: ($existing?->national_id ?? ($interactive ? text('National ID of the account holder', required: true) : null));
        $needsPassword = ! $existing || $this->option('reset-password');

        $validator = Validator::make(
            ['phone_number' => $phone, 'name' => $name, 'email' => $email, 'national_id' => $nationalId],
            [
                'phone_number' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existing?->id)],
                'national_id' => ['required', 'string', 'max:50', Rule::unique('users', 'national_id')->ignore($existing?->id)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $plainPassword = null;
        $generated = false;

        if ($needsPassword) {
            if ($this->option('generate-password')) {
                $plainPassword = Str::password(24);
                $generated = true;
            } elseif ($interactive) {
                $plainPassword = password('Password (min. 12 characters, letters and numbers)', required: true);

                if ($plainPassword !== password('Confirm password', required: true)) {
                    $this->components->error('The passwords do not match.');

                    return self::FAILURE;
                }
            } else {
                $this->components->error('Run interactively to enter a password, or pass --generate-password.');

                return self::FAILURE;
            }

            $passwordCheck = Validator::make(['password' => $plainPassword], ['password' => [Password::min(12)->letters()->numbers()]]);

            if ($passwordCheck->fails()) {
                $this->components->error($passwordCheck->errors()->first('password'));

                return self::FAILURE;
            }
        }

        $user = DB::transaction(function () use ($auditLog, $existing, $phone, $name, $email, $nationalId, $plainPassword): User {
            $user = $existing ?? new User;
            $old = $existing?->only(['name', 'email', 'phone_number', 'type']);

            $user->fill(['name' => $name, 'email' => $email, 'phone_number' => $phone, 'national_id' => $nationalId]);
            $user->type = User::TYPE_MINISTRY;

            if ($plainPassword !== null) {
                $user->password = $plainPassword;
            }

            $user->save();

            if ($existing && $plainPassword !== null) {
                $user->tokens()->delete();
            }

            $auditLog->record(
                null,
                $existing ? 'ministry_account.updated' : 'ministry_account.provisioned',
                'User',
                $user->id,
                $old,
                [...$user->only(['name', 'email', 'phone_number', 'type']), 'credential_rotated' => $plainPassword !== null],
            );

            return $user;
        });

        $this->components->info(($existing ? 'Ministry account updated' : 'Ministry account created').' (user #'.$user->id.').');

        if ($generated) {
            $this->components->warn('Generated password — shown once, store it securely now:');
            $this->line($plainPassword);
        }

        return self::SUCCESS;
    }
}
