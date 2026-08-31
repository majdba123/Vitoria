<?php

namespace App\Services;

use App\Events\AdminNotificationSent;
use App\Models\AdminNotification;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\Refund;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorMember;

class NotificationService
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService,
    ) {}

    /**
     * Send a broadcast notification (e.g. new product approved) to every
     * user who has not opted out of marketing notifications. Message in
     * Arabic. Clicking the notification takes the user to the product page.
     *
     * Notification visibility is determined solely by an explicit row in
     * admin_notification_recipients (see NotificationController::index()),
     * so a broadcast must sync the full recipient list at creation time —
     * `type` alone no longer grants visibility to anyone.
     */
    public function notifyNewProductApproved(Product $product): void
    {
        $this->broadcastToAllUsers(
            titleKey: 'notifications.new_product.title',
            bodyKey: 'notifications.new_product.body',
            actionType: AdminNotification::ACTION_PRODUCT,
            actionId: $product->id,
            replacements: fn (string $locale): array => ['product' => $this->productName($product, $locale)],
        );
    }

    /**
     * Notify admin and vendor when an order is created. Order-lifecycle
     * notices are transaction-critical (spec §33) — never filtered by
     * preference.
     */
    public function notifyNewOrder(Order $order): void
    {
        $order->load('vendor.user');
        $orderNumber = $order->order_number;
        $adminIds = User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all();
        $vendorUserId = $order->vendor?->user_id;

        if ($adminIds !== []) {
            $this->sendLocalizedNotification(
                recipientIds: $adminIds,
                titleKey: 'notifications.new_order.title',
                bodyKey: 'notifications.new_order.admin_body',
                category: NotificationPreference::CATEGORY_ORDER_UPDATES,
                actionType: AdminNotification::ACTION_ORDER,
                actionId: $order->id,
                replacements: ['order' => $orderNumber],
            );
        }

        if ($vendorUserId !== null && $vendorUserId !== 0) {
            $this->sendLocalizedNotification(
                recipientIds: [$vendorUserId],
                titleKey: 'notifications.new_order.title',
                bodyKey: 'notifications.new_order.vendor_body',
                category: NotificationPreference::CATEGORY_ORDER_UPDATES,
                actionType: AdminNotification::ACTION_ORDER,
                actionId: $order->id,
                replacements: ['order' => $orderNumber],
            );
        }
    }

    /**
     * Notify admin, order owner, and vendor when order status is updated.
     */
    public function notifyOrderStatusUpdated(Order $order, string $newStatus): void
    {
        $order->load('vendor.user', 'user');
        $orderNumber = $order->order_number;

        $bodyKey = in_array($newStatus, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_CONFIRMED], true)
            ? "notifications.order_status.{$newStatus}"
            : 'notifications.order_status.updated';

        $recipientIds = User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all();
        $recipientIds[] = $order->user_id;
        if ($order->vendor?->user_id) {
            $recipientIds[] = $order->vendor->user_id;
        }
        $recipientIds = array_unique(array_filter($recipientIds));

        if ($recipientIds === []) {
            return;
        }

        $this->sendLocalizedNotification(
            recipientIds: $recipientIds,
            titleKey: 'notifications.order_status.title',
            bodyKey: $bodyKey,
            category: NotificationPreference::CATEGORY_ORDER_UPDATES,
            actionType: AdminNotification::ACTION_ORDER,
            actionId: $order->id,
            replacements: fn (string $locale): array => [
                'order' => $orderNumber,
                'status' => trans("common.status.{$newStatus}", locale: $locale),
            ],
        );
    }

    /**
     * Notify admin and vendor when a customer requests a return (spec §12).
     */
    public function notifyReturnRequested(OrderReturn $return): void
    {
        $orderNumber = $return->order->order_number;
        $recipientIds = User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all();
        if ($recipientIds !== []) {
            $this->sendLocalizedNotification(
                recipientIds: $recipientIds,
                titleKey: 'notifications.return_requested.title',
                bodyKey: 'notifications.return_requested.body',
                category: NotificationPreference::CATEGORY_ORDER_UPDATES,
                actionType: AdminNotification::ACTION_ORDER,
                actionId: $return->order_id,
                replacements: ['order' => $orderNumber],
            );
        }

        $vendorUserId = $return->vendor?->user_id;
        if ($vendorUserId) {
            $this->sendLocalizedNotification(
                recipientIds: [$vendorUserId],
                titleKey: 'notifications.return_requested.title',
                bodyKey: 'notifications.return_requested.body',
                category: NotificationPreference::CATEGORY_ORDER_UPDATES,
                actionType: AdminNotification::ACTION_ORDER,
                actionId: $return->order_id,
                replacements: ['order' => $orderNumber],
            );
        }
    }

    /**
     * Notify the customer (and admin) when a return's status changes.
     */
    public function notifyReturnStatusUpdated(OrderReturn $return, string $newStatus): void
    {
        $orderNumber = $return->order->order_number;
        $knownStatuses = [OrderReturn::STATUS_APPROVED, OrderReturn::STATUS_REJECTED, OrderReturn::STATUS_RECEIVED, OrderReturn::STATUS_COMPLETED, OrderReturn::STATUS_CANCELLED];
        $bodyKey = in_array($newStatus, $knownStatuses, true)
            ? "notifications.return_status.{$newStatus}"
            : 'notifications.return_status.updated';

        $recipientIds = array_unique(array_filter([
            $return->user_id,
            ...User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all(),
        ]));

        if ($recipientIds === []) {
            return;
        }

        $this->sendLocalizedNotification(
            recipientIds: $recipientIds,
            titleKey: 'notifications.return_status.title',
            bodyKey: $bodyKey,
            category: NotificationPreference::CATEGORY_ORDER_UPDATES,
            actionType: AdminNotification::ACTION_ORDER,
            actionId: $return->order_id,
            replacements: ['order' => $orderNumber],
        );
    }

    /**
     * Notify the customer when a refund's status changes (spec §13).
     */
    public function notifyRefundStatusUpdated(Refund $refund, string $newStatus): void
    {
        $orderNumber = $refund->order->order_number;
        $knownStatuses = [Refund::STATUS_PENDING, Refund::STATUS_COMPLETED, Refund::STATUS_FAILED, Refund::STATUS_CANCELLED];
        $bodyKey = in_array($newStatus, $knownStatuses, true)
            ? "notifications.refund_status.{$newStatus}"
            : 'notifications.refund_status.updated';

        $recipientIds = array_unique(array_filter([$refund->order->user_id]));

        if ($recipientIds === []) {
            return;
        }

        $this->sendLocalizedNotification(
            recipientIds: $recipientIds,
            titleKey: 'notifications.refund_status.title',
            bodyKey: $bodyKey,
            category: NotificationPreference::CATEGORY_ORDER_UPDATES,
            actionType: AdminNotification::ACTION_ORDER,
            actionId: $refund->order_id,
            replacements: ['order' => $orderNumber],
        );
    }

    /**
     * Notify a user they were added as staff to a vendor (spec §22).
     */
    public function notifyVendorStaffAdded(VendorMember $member): void
    {
        $storeName = $member->vendor->store_name;
        $this->sendLocalizedNotification(
            recipientIds: [$member->user_id],
            titleKey: 'notifications.staff_added.title',
            bodyKey: 'notifications.staff_added.body',
            category: NotificationPreference::CATEGORY_ACCOUNT_SECURITY,
            actionType: null,
            actionId: null,
            replacements: fn (string $locale): array => [
                'store' => $storeName,
                'role' => trans("vendor_staff.role.{$member->role->key}", locale: $locale),
            ],
        );
    }

    /**
     * Notify admin a vendor document needs review (spec §24). Operational,
     * not transaction-critical — subject to the `vendor_compliance`
     * opt-out (spec §33), filtered from the recipient list before it is
     * ever created as a recipient row or broadcast.
     */
    public function notifyVendorDocumentSubmitted(Vendor $vendor): void
    {
        $adminIds = $this->preferenceService->filterEnabled(
            User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all(),
            NotificationPreference::CATEGORY_VENDOR_COMPLIANCE,
        );

        if ($adminIds === []) {
            return;
        }

        $this->sendLocalizedNotification(
            recipientIds: $adminIds,
            titleKey: 'notifications.vendor_document_submitted.title',
            bodyKey: 'notifications.vendor_document_submitted.body',
            category: NotificationPreference::CATEGORY_VENDOR_COMPLIANCE,
            actionType: null,
            actionId: null,
            replacements: ['store' => $vendor->store_name],
        );
    }

    /**
     * Notify the vendor owner their document was reviewed (spec §24).
     */
    public function notifyVendorDocumentReviewed(VendorDocument $document): void
    {
        $document->loadMissing('vendor.user');
        $ownerId = $document->vendor?->user_id;

        if (! $ownerId || ! $this->preferenceService->isEnabled($ownerId, NotificationPreference::CATEGORY_VENDOR_COMPLIANCE)) {
            return;
        }

        $statusKey = $document->status === VendorDocument::STATUS_VERIFIED ? 'verified' : 'rejected';
        $this->sendLocalizedNotification(
            recipientIds: [$ownerId],
            titleKey: 'notifications.vendor_document_reviewed.title',
            bodyKey: "notifications.vendor_document_reviewed.{$statusKey}",
            category: NotificationPreference::CATEGORY_VENDOR_COMPLIANCE,
            actionType: null,
            actionId: null,
            replacements: ['document' => $document->type],
        );
    }

    /**
     * Notify admin a product document needs review (spec §25).
     */
    public function notifyProductDocumentSubmitted(ProductDocument $document): void
    {
        $adminIds = $this->preferenceService->filterEnabled(
            User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all(),
            NotificationPreference::CATEGORY_VENDOR_COMPLIANCE,
        );

        if ($adminIds === []) {
            return;
        }

        $document->loadMissing('product:id,name');

        $this->sendLocalizedNotification(
            recipientIds: $adminIds,
            titleKey: 'notifications.product_document_submitted.title',
            bodyKey: 'notifications.product_document_submitted.body',
            category: NotificationPreference::CATEGORY_VENDOR_COMPLIANCE,
            actionType: AdminNotification::ACTION_PRODUCT,
            actionId: $document->product_id,
            replacements: fn (string $locale): array => ['product' => $this->productName($document->product, $locale)],
        );
    }

    /**
     * Notify the owning vendor a product document was reviewed (spec §25).
     */
    public function notifyProductDocumentReviewed(ProductDocument $document): void
    {
        $document->loadMissing(['vendor:id,user_id', 'product:id,name']);
        $ownerId = $document->vendor?->user_id;

        if (! $ownerId || ! $this->preferenceService->isEnabled($ownerId, NotificationPreference::CATEGORY_VENDOR_COMPLIANCE)) {
            return;
        }

        $statusKey = $document->status === ProductDocument::STATUS_APPROVED ? 'approved' : 'rejected';
        $this->sendLocalizedNotification(
            recipientIds: [$ownerId],
            titleKey: 'notifications.product_document_reviewed.title',
            bodyKey: "notifications.product_document_reviewed.{$statusKey}",
            category: NotificationPreference::CATEGORY_VENDOR_COMPLIANCE,
            actionType: AdminNotification::ACTION_PRODUCT,
            actionId: $document->product_id,
            replacements: fn (string $locale): array => ['product' => $this->productName($document->product, $locale)],
        );
    }

    /**
     * Notify all users when a product gets a new discount (public, Arabic).
     * Clicking opens the product page.
     */
    public function notifyProductDiscountAdded(Product $product): void
    {
        $pct = $product->discount_percentage ? (int) round((float) $product->discount_percentage) : 0;
        $this->broadcastToAllUsers(
            titleKey: 'notifications.discount_added.title',
            bodyKey: 'notifications.discount_added.body',
            actionType: AdminNotification::ACTION_PRODUCT,
            actionId: $product->id,
            replacements: fn (string $locale): array => [
                'product' => $this->productName($product, $locale),
                'discount' => $pct > 0 ? " ({$pct}%)" : '',
            ],
        );
    }

    /**
     * Notify all users when a product's discount is updated and still active (public, Arabic).
     */
    public function notifyProductDiscountUpdated(Product $product): void
    {
        $pct = $product->discount_percentage ? (int) round((float) $product->discount_percentage) : 0;
        $this->broadcastToAllUsers(
            titleKey: 'notifications.discount_updated.title',
            bodyKey: 'notifications.discount_updated.body',
            actionType: AdminNotification::ACTION_PRODUCT,
            actionId: $product->id,
            replacements: fn (string $locale): array => [
                'product' => $this->productName($product, $locale),
                'discount' => $pct > 0 ? " ({$pct}%)" : '',
            ],
        );
    }

    /**
     * Create a marketing-category notification and sync every user who has
     * not opted out of marketing as an explicit recipient, then broadcast
     * it. Shared by the three product-marketing notices above so the
     * recipient-sync logic lives in one place.
     */
    /**
     * @param  array<string, scalar>|callable(string): array<string, scalar>  $replacements
     */
    private function broadcastToAllUsers(
        string $titleKey,
        string $bodyKey,
        string $actionType,
        int $actionId,
        array|callable $replacements = [],
    ): void
    {
        $recipientIds = $this->preferenceService->filterEnabled(
            User::query()->pluck('id')->all(),
            NotificationPreference::CATEGORY_MARKETING,
        );

        if ($recipientIds === []) {
            return;
        }

        $this->sendLocalizedNotification(
            recipientIds: $recipientIds,
            titleKey: $titleKey,
            bodyKey: $bodyKey,
            category: NotificationPreference::CATEGORY_MARKETING,
            actionType: $actionType,
            actionId: $actionId,
            replacements: $replacements,
            type: AdminNotification::TYPE_PUBLIC,
        );
    }

    /**
     * Snapshot one translated notification per recipient locale. Recipient
     * rows remain explicit, so splitting by language cannot widen visibility.
     *
     * @param  array<int>  $recipientIds
     * @param  array<string, scalar>|callable(string): array<string, scalar>  $replacements
     */
    private function sendLocalizedNotification(
        array $recipientIds,
        string $titleKey,
        string $bodyKey,
        string $category,
        ?string $actionType,
        ?int $actionId,
        array|callable $replacements = [],
        string $type = AdminNotification::TYPE_PRIVATE,
    ): void {
        $recipientsByLocale = User::query()
            ->whereIn('id', array_values(array_unique($recipientIds)))
            ->get(['id', 'locale'])
            ->groupBy(fn (User $user): string => in_array($user->locale, ['ar', 'en'], true)
                ? $user->locale
                : (string) config('app.locale', 'ar'));

        foreach ($recipientsByLocale as $locale => $recipients) {
            $localizedReplacements = is_callable($replacements) ? $replacements($locale) : $replacements;
            $localizedRecipientIds = $recipients->pluck('id')->map(fn ($id): int => (int) $id)->all();

            $notification = AdminNotification::query()->create([
                'title' => trans($titleKey, locale: $locale),
                'body' => trans($bodyKey, $localizedReplacements, $locale),
                'type' => $type,
                'category' => $category,
                'action_type' => $actionType,
                'action_id' => $actionId,
                'sent_by' => null,
            ]);
            $notification->recipients()->sync($localizedRecipientIds);
            $this->broadcastNotification($notification, $localizedRecipientIds);
        }
    }

    private function productName(?Product $product, string $locale): string
    {
        if (! $product) {
            return trans('common.not_available', locale: $locale);
        }

        return $locale === 'ar'
            ? ($product->name_ar ?: $product->name_en ?: $product->name)
            : ($product->name_en ?: $product->name_ar ?: $product->name);
    }

    /**
     * Broadcast a notification to its recipients.
     *
     * @param  array<int>  $recipientUserIds
     */
    private function broadcastNotification(AdminNotification $notification, array $recipientUserIds): void
    {
        AdminNotificationSent::dispatch(
            $notification->id,
            $notification->title,
            $notification->body,
            $notification->type,
            $recipientUserIds,
            $notification->action_type,
            $notification->action_id !== null ? (int) $notification->action_id : null,
        );
    }
}
