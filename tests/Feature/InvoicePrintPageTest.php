<?php

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vendor;

/**
 * Order invoice print page (stakeholder reporting batch, invoice formatting).
 *
 * The invoice is an immutable accounting snapshot: printing it must never
 * change the numbers it shows, must always show the real line-item
 * quantity, and must only be reachable by the customer who placed the
 * order (or staff), never by an unrelated shopper.
 */
function makeInvoiceForPrintTest(array $overrides = []): Invoice
{
    $vendor = Vendor::factory()->create();
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    $order = Order::factory()->for($vendor)->for($customer, 'user')->create(['status' => Order::STATUS_COMPLETED]);
    OrderItem::factory()->for($order)->create(['product_name' => 'Test Product', 'quantity' => 4, 'unit_price' => 50, 'line_total' => 200]);

    $invoice = Invoice::factory()->create(array_merge([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'user_id' => $customer->id,
        'subtotal_amount' => 200,
        'discount_total' => 0,
        'shipping_total' => 20,
        'tax_total' => 0,
        'grand_total' => 220,
    ], $overrides));

    return $invoice->fresh(['order.items', 'vendor', 'user']);
}

it('renders the invoice print page with unaltered quantities and totals', function () {
    $invoice = makeInvoiceForPrintTest();

    $response = $this->actingAs($invoice->user)->get(route('invoices.print', $invoice));

    $response->assertOk();
    $response->assertSee('Test Product');
    $response->assertSee('4', false);
    $response->assertSee(number_format(200, 2), false);
    $response->assertSee(number_format(220, 2), false);
});

it('shows the discount and tax lines only when the invoice actually carries them', function () {
    $withoutExtras = makeInvoiceForPrintTest();
    $this->actingAs($withoutExtras->user)
        ->get(route('invoices.print', $withoutExtras))
        ->assertDontSee(__('invoices.discount'))
        ->assertDontSee(__('invoices.tax'));

    $withExtras = makeInvoiceForPrintTest(['discount_total' => 30, 'tax_total' => 10, 'grand_total' => 200]);
    $this->actingAs($withExtras->user)
        ->get(route('invoices.print', $withExtras))
        ->assertSee(__('invoices.discount'))
        ->assertSee(__('invoices.tax'));
});

it('includes a working print control that is hidden from the printed output', function () {
    $invoice = makeInvoiceForPrintTest();

    $response = $this->actingAs($invoice->user)->get(route('invoices.print', $invoice));

    $response->assertOk();
    $response->assertSee('window.print()', false);
    $response->assertSee('.print-bar', false);
    $response->assertSee('display: none !important', false);
});

it('denies the invoice print page to an unrelated customer', function () {
    $invoice = makeInvoiceForPrintTest();
    $otherCustomer = User::factory()->create(['type' => User::TYPE_USER]);

    $this->actingAs($otherCustomer)
        ->get(route('invoices.print', $invoice))
        ->assertForbidden();
});
