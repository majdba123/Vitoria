<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Customer address book (spec §6).
 *
 * Every single-record action authorizes through UserAddressPolicy, so an
 * address id belonging to another customer returns 403 rather than data.
 */
class UserAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->addresses()->get()->map(
                fn (UserAddress $address) => $this->present($address)
            ),
        ]);
    }

    public function store(StoreUserAddressRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // The first address a customer saves becomes their default, so
        // checkout always has something preselected.
        $isFirst = ! $user->addresses()->exists();
        $shouldBeDefault = $isFirst || (bool) ($data['is_default'] ?? false);

        $address = DB::transaction(function () use ($data, $shouldBeDefault, $user) {
            if ($shouldBeDefault) {
                $this->clearDefaultFor((int) $user->id);
            }

            return UserAddress::create(array_merge($data, [
                'user_id' => $user->id,
                'is_default' => $shouldBeDefault,
            ]));
        });

        return response()->json(['data' => $this->present($address)], 201);
    }

    public function update(StoreUserAddressRequest $request, UserAddress $address): JsonResponse
    {
        $this->authorize('update', $address);

        $data = $request->validated();
        $shouldBeDefault = (bool) ($data['is_default'] ?? false);

        DB::transaction(function () use ($address, $data, $shouldBeDefault): void {
            if ($shouldBeDefault) {
                $this->clearDefaultFor((int) $address->user_id);
            }

            $address->update(array_merge($data, [
                // Never demote the current default implicitly — that would
                // leave the customer with no default at all.
                'is_default' => $shouldBeDefault ?: $address->is_default,
            ]));
        });

        return response()->json(['data' => $this->present($address->fresh())]);
    }

    public function destroy(UserAddress $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $userId = (int) $address->user_id;
        $wasDefault = (bool) $address->is_default;

        DB::transaction(function () use ($address, $userId, $wasDefault): void {
            // Soft delete: historical orders hold their own address snapshot
            // and never reference this row (decision D5).
            $address->delete();

            if ($wasDefault) {
                UserAddress::query()
                    ->where('user_id', $userId)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->update(['is_default' => true]);
            }
        });

        return response()->json(['message' => __('addresses.deleted')]);
    }

    public function setDefault(UserAddress $address): JsonResponse
    {
        $this->authorize('update', $address);

        DB::transaction(function () use ($address): void {
            $this->clearDefaultFor((int) $address->user_id);
            $address->update(['is_default' => true]);
        });

        return response()->json(['data' => $this->present($address->fresh())]);
    }

    private function clearDefaultFor(int $userId): void
    {
        UserAddress::query()
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(UserAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'label_name' => __("addresses.label.{$address->label}"),
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'alternate_phone' => $address->alternate_phone,
            'governorate' => $address->governorate,
            'city_id' => $address->city_id,
            'city' => $address->city,
            'district' => $address->district,
            'street' => $address->street,
            'building' => $address->building,
            'floor' => $address->floor,
            'landmark' => $address->landmark,
            'postal_code' => $address->postal_code,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'notes' => $address->notes,
            'is_default' => (bool) $address->is_default,
        ];
    }
}
