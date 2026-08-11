<?php

namespace App\Services;

use App\Events\AdminNotificationSent;
use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;

class NotificationService
{
    /**
     * Send a public notification (e.g. new product approved). Message in Arabic.
     * Clicking the notification takes the user to the product page.
     */
    public function notifyNewProductApproved(Product $product): void
    {
        $body = 'منتج جديد متوفر الآن: '.$product->name;

        $notification = AdminNotification::query()->create([
            'title' => 'منتج جديد',
            'body' => $body,
            'type' => AdminNotification::TYPE_PUBLIC,
            'action_type' => AdminNotification::ACTION_PRODUCT,
            'action_id' => $product->id,
            'sent_by' => null,
        ]);

        $this->broadcastNotification($notification, []);
    }

    /**
     * Notify admin and vendor when an order is created.
     */
    public function notifyNewOrder(Order $order): void
    {
        $order->load('vendor.user');
        $orderNumber = $order->order_number;
        $bodyAdmin = "طلب جديد تم إنشاؤه. رقم الطلب: #{$orderNumber}";
        $bodyVendor = "لديك طلب جديد. رقم الطلب: #{$orderNumber}";

        $adminIds = User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all();
        $vendorUserId = $order->vendor?->user_id;

        if ($adminIds !== []) {
            $notification = AdminNotification::query()->create([
                'title' => 'طلب جديد',
                'body' => $bodyAdmin,
                'type' => AdminNotification::TYPE_PRIVATE,
                'action_type' => AdminNotification::ACTION_ORDER,
                'action_id' => $order->id,
                'sent_by' => null,
            ]);
            $notification->recipients()->sync($adminIds);
            $this->broadcastNotification($notification, $adminIds);
        }

        if ($vendorUserId !== null && $vendorUserId !== 0) {
            $notifVendor = AdminNotification::query()->create([
                'title' => 'طلب جديد',
                'body' => $bodyVendor,
                'type' => AdminNotification::TYPE_PRIVATE,
                'action_type' => AdminNotification::ACTION_ORDER,
                'action_id' => $order->id,
                'sent_by' => null,
            ]);
            $notifVendor->recipients()->sync([$vendorUserId]);
            $this->broadcastNotification($notifVendor, [$vendorUserId]);
        }
    }

    /**
     * Notify admin, order owner, and vendor when order status is updated.
     */
    public function notifyOrderStatusUpdated(Order $order, string $newStatus): void
    {
        $order->load('vendor.user', 'user');
        $orderNumber = $order->order_number;

        $statusMessages = [
            'completed' => "تم إكمال الطلب #{$orderNumber}",
            'cancelled' => "تم إلغاء الطلب #{$orderNumber}",
            'confirmed' => "تم تأكيد الطلب #{$orderNumber}",
        ];
        $body = $statusMessages[$newStatus] ?? "تم تحديث حالة الطلب #{$orderNumber} إلى: {$newStatus}";

        $recipientIds = User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all();
        $recipientIds[] = $order->user_id;
        if ($order->vendor?->user_id) {
            $recipientIds[] = $order->vendor->user_id;
        }
        $recipientIds = array_unique(array_filter($recipientIds));

        if ($recipientIds === []) {
            return;
        }

        $notification = AdminNotification::query()->create([
            'title' => 'تحديث الطلب',
            'body' => $body,
            'type' => AdminNotification::TYPE_PRIVATE,
            'action_type' => AdminNotification::ACTION_ORDER,
            'action_id' => $order->id,
            'sent_by' => null,
        ]);
        $notification->recipients()->sync($recipientIds);

        $this->broadcastNotification($notification, $recipientIds);
    }

    /**
     * Notify admin and vendor when a customer requests a return (spec §12).
     */
    public function notifyReturnRequested(OrderReturn $return): void
    {
        $orderNumber = $return->order->order_number;
        $bodyAdmin = "طلب إرجاع جديد لطلب #{$orderNumber}";
        $bodyVendor = "طلب إرجاع جديد لطلب #{$orderNumber}";

        $recipientIds = User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all();

        $notification = AdminNotification::query()->create([
            'title' => 'طلب إرجاع',
            'body' => $bodyAdmin,
            'type' => AdminNotification::TYPE_PRIVATE,
            'action_type' => AdminNotification::ACTION_ORDER,
            'action_id' => $return->order_id,
            'sent_by' => null,
        ]);
        if ($recipientIds !== []) {
            $notification->recipients()->sync($recipientIds);
            $this->broadcastNotification($notification, $recipientIds);
        }

        $vendorUserId = $return->vendor?->user_id;
        if ($vendorUserId) {
            $notifVendor = AdminNotification::query()->create([
                'title' => 'طلب إرجاع',
                'body' => $bodyVendor,
                'type' => AdminNotification::TYPE_PRIVATE,
                'action_type' => AdminNotification::ACTION_ORDER,
                'action_id' => $return->order_id,
                'sent_by' => null,
            ]);
            $notifVendor->recipients()->sync([$vendorUserId]);
            $this->broadcastNotification($notifVendor, [$vendorUserId]);
        }
    }

    /**
     * Notify the customer (and admin) when a return's status changes.
     */
    public function notifyReturnStatusUpdated(OrderReturn $return, string $newStatus): void
    {
        $orderNumber = $return->order->order_number;
        $statusMessages = [
            OrderReturn::STATUS_APPROVED => "تمت الموافقة على طلب إرجاعك للطلب #{$orderNumber}",
            OrderReturn::STATUS_REJECTED => "تم رفض طلب إرجاعك للطلب #{$orderNumber}",
            OrderReturn::STATUS_RECEIVED => "تم استلام المرتجع الخاص بالطلب #{$orderNumber}",
            OrderReturn::STATUS_COMPLETED => "اكتمل إرجاع الطلب #{$orderNumber}",
            OrderReturn::STATUS_CANCELLED => "تم إلغاء طلب إرجاع الطلب #{$orderNumber}",
        ];
        $body = $statusMessages[$newStatus] ?? "تم تحديث حالة إرجاع الطلب #{$orderNumber}";

        $recipientIds = array_unique(array_filter([
            $return->user_id,
            ...User::query()->where('type', User::TYPE_ADMIN)->pluck('id')->all(),
        ]));

        if ($recipientIds === []) {
            return;
        }

        $notification = AdminNotification::query()->create([
            'title' => 'تحديث الإرجاع',
            'body' => $body,
            'type' => AdminNotification::TYPE_PRIVATE,
            'action_type' => AdminNotification::ACTION_ORDER,
            'action_id' => $return->order_id,
            'sent_by' => null,
        ]);
        $notification->recipients()->sync($recipientIds);

        $this->broadcastNotification($notification, $recipientIds);
    }

    /**
     * Notify the customer when a refund's status changes (spec §13).
     */
    public function notifyRefundStatusUpdated(Refund $refund, string $newStatus): void
    {
        $orderNumber = $refund->order->order_number;
        $statusMessages = [
            Refund::STATUS_PENDING => "تم إنشاء طلب استرداد لطلبك #{$orderNumber}",
            Refund::STATUS_COMPLETED => "تم استرداد المبلغ لطلبك #{$orderNumber}",
            Refund::STATUS_FAILED => "فشلت عملية استرداد المبلغ لطلبك #{$orderNumber}",
            Refund::STATUS_CANCELLED => "تم إلغاء طلب استرداد المبلغ لطلبك #{$orderNumber}",
        ];
        $body = $statusMessages[$newStatus] ?? "تم تحديث حالة استرداد المبلغ لطلبك #{$orderNumber}";

        $recipientIds = array_unique(array_filter([$refund->order->user_id]));

        if ($recipientIds === []) {
            return;
        }

        $notification = AdminNotification::query()->create([
            'title' => 'تحديث الاسترداد',
            'body' => $body,
            'type' => AdminNotification::TYPE_PRIVATE,
            'action_type' => AdminNotification::ACTION_ORDER,
            'action_id' => $refund->order_id,
            'sent_by' => null,
        ]);
        $notification->recipients()->sync($recipientIds);

        $this->broadcastNotification($notification, $recipientIds);
    }

    /**
     * Notify all users when a product gets a new discount (public, Arabic).
     * Clicking opens the product page.
     */
    public function notifyProductDiscountAdded(Product $product): void
    {
        $pct = $product->discount_percentage ? (int) round((float) $product->discount_percentage) : 0;
        $body = "خصم جديد على المنتج: {$product->name}".($pct > 0 ? " ({$pct}%)" : '');

        $notification = AdminNotification::query()->create([
            'title' => 'خصم على منتج',
            'body' => $body,
            'type' => AdminNotification::TYPE_PUBLIC,
            'action_type' => AdminNotification::ACTION_PRODUCT,
            'action_id' => $product->id,
            'sent_by' => null,
        ]);

        $this->broadcastNotification($notification, []);
    }

    /**
     * Notify all users when a product's discount is updated and still active (public, Arabic).
     */
    public function notifyProductDiscountUpdated(Product $product): void
    {
        $pct = $product->discount_percentage ? (int) round((float) $product->discount_percentage) : 0;
        $body = "تم تحديث الخصم على المنتج: {$product->name}".($pct > 0 ? " ({$pct}%)" : '');

        $notification = AdminNotification::query()->create([
            'title' => 'تحديث خصم منتج',
            'body' => $body,
            'type' => AdminNotification::TYPE_PUBLIC,
            'action_type' => AdminNotification::ACTION_PRODUCT,
            'action_id' => $product->id,
            'sent_by' => null,
        ]);

        $this->broadcastNotification($notification, []);
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
