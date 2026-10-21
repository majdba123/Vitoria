<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\SyncAdminRolesRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\Order;
use App\Models\ProductPhoto;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\AdminAccessException;
use App\Services\Admin\AdminRbacService;
use App\Services\Admin\UserService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct(
        public UserService $userService,
        public AuditLogService $auditLogService,
        public AdminRbacService $rbac,
    ) {}

    /**
     * List all users.
     */
    public function index(): JsonResponse
    {
        $perPage = min(max((int) request('per_page', 15), 1), 100);
        $isCustomerView = request()->filled('type') && (int) request('type') === User::TYPE_USER;

        $users = User::query()
            ->when(request()->filled('type'), fn ($query) => $query->where('type', (int) request('type')))
            ->when(request()->filled('search'), function ($query) {
                $term = '%'.request('search').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone_number', 'like', $term));
            })
            ->when(request()->filled('city_id'), fn ($query) => $query->where('city_id', (int) request('city_id')))
            ->when(request()->filled('product_type'), fn ($query) => $query->where('preferred_product_type', request('product_type')))
            ->when(request()->filled('status'), fn ($query) => request('status') === 'verified'
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at'))
            ->when($isCustomerView, fn ($query) => $query
                ->with('city:id,name')
                ->withCount(['orders as orders_count' => fn ($q) => $q->where('status', '!=', Order::STATUS_CANCELLED)])
                ->withSum(['orders as total_purchases' => fn ($q) => $q->where('status', '!=', Order::STATUS_CANCELLED)], 'grand_total')
                ->withMax('orders as last_order_at', 'created_at'))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => __('Users retrieved successfully.'),
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Show a single user.
     */
    public function show(User $user): JsonResponse
    {
        if ($user->type === User::TYPE_EMPLOYEE) {
            $user->load('employeeRoles');
        }

        if ($user->type === User::TYPE_USER) {
            $user->load('city:id,name');
            $user->loadCount(['orders as orders_count' => fn ($q) => $q->where('status', '!=', Order::STATUS_CANCELLED)]);
            $user->loadSum(['orders as total_purchases' => fn ($q) => $q->where('status', '!=', Order::STATUS_CANCELLED)], 'grand_total');
            $user->loadMax('orders as last_order_at', 'created_at');
        }

        return response()->json([
            'message' => __('User retrieved successfully.'),
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Assign this employee's role(s) — stakeholder review #24. Restricted to
     * the employee-designated roles only, so an admin can never use this
     * endpoint to hand an employee a vendor-scoped role (owner/manager/...)
     * that was never meant to apply outside a vendor_members membership.
     */
    public function updateEmployeeRoles(Request $request, User $user): JsonResponse
    {
        if ($user->type !== User::TYPE_EMPLOYEE) {
            abort(422, __('Roles can only be assigned to employee accounts.'));
        }

        $validated = $request->validate([
            'role_keys' => ['present', 'array'],
            'role_keys.*' => ['string', \Illuminate\Validation\Rule::in([Role::KEY_CATALOG_MODERATOR, Role::KEY_ORDER_REVIEWER])],
        ]);

        $roleIds = Role::query()
            ->where('scope', Role::SCOPE_EMPLOYEE)
            ->whereIn('key', $validated['role_keys'])
            ->pluck('id');

        DB::transaction(function () use ($request, $user, $roleIds) {
            $previousKeys = $user->employeeRoles()->pluck('key')->sort()->values()->all();
            $user->employeeRoles()->sync($roleIds);
            $newKeys = $user->employeeRoles()->pluck('key')->sort()->values()->all();

            if ($previousKeys !== $newKeys) {
                $this->auditLogService->record(
                    $request->user(),
                    'user.employee_roles_changed',
                    'User',
                    $user->id,
                    ['role_keys' => $previousKeys],
                    ['role_keys' => $newKeys],
                );
            }
        });

        return response()->json([
            'message' => __('Employee roles updated successfully.'),
            'data' => new UserResource($user->load('employeeRoles')),
        ]);
    }

    /**
     * List a specific user's favourite products (admin view).
     */
    public function favourites(User $user): JsonResponse
    {
        $products = $user->favouriteProducts()
            ->with([
                'photos' => function ($q) {
                    $q->orderByRaw(
                        'CASE WHEN image_type = ? THEN 0 WHEN is_primary = 1 THEN 1 ELSE 2 END',
                        [ProductPhoto::TYPE_PRIMARY]
                    )->orderBy('sort_order')->limit(1);
                },
                'vendor:id,store_name',
                'category:id,name',
            ])
            ->select(['products.id', 'products.name', 'products.price', 'products.vendor_id', 'products.category_id', 'products.quantity'])
            ->latest('favourites.created_at')
            ->get();

        $mapped = $products->map(function ($product) {
            $photo = $product->photos->first();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $product->quantity,
                'first_photo_url' => $photo ? asset('storage/'.$photo->path) : null,
                'vendor' => $product->vendor ? ['id' => $product->vendor->id, 'store_name' => $product->vendor->store_name] : null,
                'category' => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            ];
        });

        return response()->json([
            'message' => __('User favourites retrieved successfully.'),
            'data' => $mapped,
        ]);
    }

    /**
     * Create a new user. Minting an admin-type account requires
     * `roles.assign`; the new admin starts with no back-office permissions
     * until roles are explicitly assigned.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $actor = $request->user();

        try {
            if ((int) ($validated['type'] ?? User::TYPE_USER) === User::TYPE_ADMIN) {
                $this->rbac->assertMayCreateAdmins($actor);
            }
        } catch (AdminAccessException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        $user = DB::transaction(function () use ($validated, $actor) {
            $user = $this->userService->create($validated);

            $this->auditLogService->record($actor, 'user.created', 'User', $user->id, null, $this->auditableProfile($user));

            return $user;
        });

        return response()->json([
            'message' => __('User created successfully.'),
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Update an existing user. Changing another administrator's account
     * (including its password) is limited to actors who hold every
     * permission the target holds; a super administrator's account only to
     * another super administrator.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();
        $actor = $request->user();

        try {
            $user = DB::transaction(function () use ($validated, $actor, $user) {
                $this->rbac->assertCanChangeAccount($actor, $user, $validated);

                $before = $this->auditableProfile($user);
                $previousType = $user->type;
                $passwordChanged = isset($validated['password']) && $validated['password'] !== '';

                $user = $this->userService->update($user, $validated);

                if ($user->type !== $previousType && $previousType === User::TYPE_ADMIN) {
                    $this->rbac->revokeAllAdminRoles($actor, $user);
                }

                if ($passwordChanged && ! $actor->is($user)) {
                    $user->tokens()->delete();
                }

                // A user's `type` is the closest thing this app has to a role — a
                // change here is exactly the "user role change" spec §35 names.
                if ($user->type !== $previousType) {
                    $this->auditLogService->record($actor, 'user.role_changed', 'User', $user->id, ['type' => $previousType], ['type' => $user->type]);
                }

                $after = $this->auditableProfile($user);
                $changedKeys = array_keys(array_filter($after, fn ($value, $key) => ($before[$key] ?? null) !== $value && $key !== 'type', ARRAY_FILTER_USE_BOTH));

                if ($changedKeys !== [] || $passwordChanged) {
                    $this->auditLogService->record(
                        $actor,
                        'user.updated',
                        'User',
                        $user->id,
                        array_intersect_key($before, array_flip($changedKeys)),
                        array_intersect_key($after, array_flip($changedKeys)) + ($passwordChanged ? ['password_changed' => true] : []),
                    );
                }

                return $user;
            });
        } catch (AdminAccessException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        return response()->json([
            'message' => __('User updated successfully.'),
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->orders()->exists() || $user->reviews()->exists()) {
            return response()->json([
                'message' => __('This user cannot be deleted while they still have order or review history.'),
            ], 422);
        }

        $actor = $request->user();

        try {
            DB::transaction(function () use ($actor, $user) {
                $this->rbac->assertCanDeleteAccount($actor, $user);

                $snapshot = $this->auditableProfile($user) + [
                    'admin_role_keys' => $user->adminRoles()->pluck('key')->values()->all(),
                ];

                $this->auditLogService->record($actor, 'user.deleted', 'User', $user->id, $snapshot, null);
                $this->userService->delete($user);
            });
        } catch (AdminAccessException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        return response()->json([
            'message' => __('User deleted successfully.'),
        ]);
    }

    /**
     * Replace an administrator's platform-admin roles.
     */
    public function updateAdminRoles(SyncAdminRolesRequest $request, User $user): JsonResponse
    {
        try {
            $this->rbac->syncAdminRoles($request->user(), $user, array_map('intval', $request->validated('role_ids')));
        } catch (AdminAccessException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->status);
        }

        return response()->json([
            'message' => __('admin.rbac_roles_assigned'),
            'data' => $this->rbac->effectivePermissions($user),
        ]);
    }

    /**
     * An administrator's roles and effective permissions, with the role(s)
     * each permission comes from.
     */
    public function permissions(Request $request, User $user): JsonResponse
    {
        $data = $this->rbac->effectivePermissions($user);
        $actor = $request->user();

        try {
            $this->rbac->assertCanManageAccount($actor, $user);
            $data['can_manage'] = ! $actor->is($user) && $user->isAdmin();
        } catch (AdminAccessException) {
            $data['can_manage'] = false;
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Identity fields safe to keep in the audit trail. Never the password,
     * remember token, or other credentials.
     *
     * @return array<string, mixed>
     */
    private function auditableProfile(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'national_id' => $user->national_id,
            'membership_number' => $user->membership_number,
            'age' => $user->age,
            'type' => $user->type,
        ];
    }
}
