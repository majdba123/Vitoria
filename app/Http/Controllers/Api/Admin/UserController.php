<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\Order;
use App\Models\ProductPhoto;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\UserService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        public UserService $userService,
        public AuditLogService $auditLogService,
    ) {}

    /**
     * List all users.
     */
    public function index(): JsonResponse
    {
        $perPage = min((int) request('per_page', 15), 500);
        $isCustomerView = request()->filled('type') && (int) request('type') === User::TYPE_USER;

        $users = User::query()
            ->when(request()->filled('type'), fn ($query) => $query->where('type', (int) request('type')))
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

        $roleIds = Role::query()->whereIn('key', $validated['role_keys'])->pluck('id');
        $user->employeeRoles()->sync($roleIds);

        $this->auditLogService->record(
            $request->user(),
            'user.employee_roles_changed',
            'User',
            $user->id,
            [],
            ['role_keys' => $validated['role_keys']],
        );

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
     * Create a new user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'message' => __('User created successfully.'),
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Update an existing user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();
        $previousType = $user->type;
        $user = $this->userService->update($user, $validated);

        // A user's `type` is the closest thing this app has to a role — a
        // change here is exactly the "user role change" spec §35 names.
        if (array_key_exists('type', $validated) && $user->type !== $previousType) {
            $this->auditLogService->record(
                $request->user(),
                'user.role_changed',
                'User',
                $user->id,
                ['type' => $previousType],
                ['type' => $user->type],
            );
        }

        return response()->json([
            'message' => __('User updated successfully.'),
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->orders()->exists() || $user->reviews()->exists()) {
            return response()->json([
                'message' => __('This user cannot be deleted while they still have order or review history.'),
            ], 422);
        }

        $this->userService->delete($user);

        return response()->json([
            'message' => __('User deleted successfully.'),
        ]);
    }
}
