<?php

use App\Models\ImportShipment;
use App\Models\ImportShipmentItem;
use App\Models\Port;
use App\Models\Syndicate;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * @return array<string, mixed>
 */
function importShipmentPayload(Port $port, array $overrides = []): array
{
    return array_merge([
        'merchant_name' => 'أحمد التاجر',
        'merchant_register_number' => 'REG-100',
        'merchant_address' => 'دمشق - المزة',
        'invoice_number' => 'INV-500',
        'port_id' => $port->id,
        'stickers_count' => 10,
        'sticker_price' => 400,
        'clearance_number' => 'CLR-77',
        'release_decision' => 'قرار إفراج رقم 12',
        'items' => [
            ['name' => 'لقاح', 'country' => 'Germany', 'quantity' => 5, 'total_price' => 1500.5],
            ['name' => 'مضاد حيوي', 'country' => 'Turkey', 'quantity' => 3, 'total_price' => 300],
        ],
    ], $overrides);
}

function syndicateUserWithProfile(): User
{
    $user = User::factory()->syndicate()->create();
    Syndicate::factory()->create(['user_id' => $user->id]);

    return $user->fresh();
}

test('migration seeds the default ports', function () {
    expect(Port::query()->count())->toBe(count(Port::DEFAULT_NAMES))
        ->and(Port::query()->where('name', 'معبر نصيب')->exists())->toBeTrue();
});

test('admin can create, view, update, list and delete a shipment with items', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $port = Port::factory()->create();

    $created = $this->postJson('/api/admin/import-shipments', importShipmentPayload($port))
        ->assertCreated()
        ->assertJsonPath('data.merchant_name', 'أحمد التاجر')
        ->assertJsonPath('data.stickers_total', 4000)
        ->assertJsonPath('data.port.id', $port->id)
        ->assertJsonCount(2, 'data.items');

    $id = $created->json('data.id');

    $this->getJson("/api/admin/import-shipments/{$id}")->assertOk()->assertJsonPath('data.invoice_number', 'INV-500');

    $this->putJson("/api/admin/import-shipments/{$id}", importShipmentPayload($port, [
        'sticker_price' => 200,
        'items' => [['name' => 'علف', 'country' => 'Iran', 'quantity' => 1, 'total_price' => 50]],
    ]))
        ->assertOk()
        ->assertJsonPath('data.stickers_total', 2000)
        ->assertJsonCount(1, 'data.items');

    expect(ImportShipmentItem::query()->count())->toBe(1);

    $this->getJson('/api/admin/import-shipments')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.items_count', 1);

    $this->deleteJson("/api/admin/import-shipments/{$id}")->assertOk();

    expect(ImportShipment::query()->count())->toBe(0)
        ->and(ImportShipmentItem::query()->count())->toBe(0);
});

test('employee can create and manage shipments', function () {
    Sanctum::actingAs(User::factory()->employee()->create());
    $port = Port::factory()->create();

    $id = $this->postJson('/api/employee/import-shipments', importShipmentPayload($port))
        ->assertCreated()
        ->json('data.id');

    $this->getJson('/api/employee/import-shipments')->assertOk()->assertJsonPath('meta.total', 1);
    $this->deleteJson("/api/employee/import-shipments/{$id}")->assertOk();
});

test('syndicate shipments are assigned to and scoped by the syndicate', function () {
    $port = Port::factory()->create();
    $syndicateUser = syndicateUserWithProfile();
    $otherSyndicate = Syndicate::factory()->create();
    $foreign = ImportShipment::factory()->create(['syndicate_id' => $otherSyndicate->id, 'port_id' => $port->id]);

    Sanctum::actingAs($syndicateUser);

    $this->postJson('/api/syndicate/import-shipments', importShipmentPayload($port, ['syndicate_id' => $otherSyndicate->id]))
        ->assertCreated()
        ->assertJsonPath('data.syndicate_id', $syndicateUser->syndicate->id);

    $this->getJson('/api/syndicate/import-shipments')->assertOk()->assertJsonPath('meta.total', 1);
    $this->getJson("/api/syndicate/import-shipments/{$foreign->id}")->assertNotFound();
    $this->putJson("/api/syndicate/import-shipments/{$foreign->id}", importShipmentPayload($port))->assertNotFound();
    $this->deleteJson("/api/syndicate/import-shipments/{$foreign->id}")->assertNotFound();
    $this->getJson('/api/syndicate/import-shipments/stats')->assertOk()->assertJsonPath('data.totals.shipments_count', 1);

    expect($foreign->fresh())->not->toBeNull();
});

test('vendors and customers cannot reach shipment endpoints', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/admin/import-shipments')->assertForbidden();
    $this->getJson('/api/employee/import-shipments')->assertForbidden();
    $this->getJson('/api/syndicate/import-shipments')->assertForbidden();
});

test('shipment validation enforces sticker price, active port and at least one item', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $inactive = Port::factory()->inactive()->create();

    $this->postJson('/api/admin/import-shipments', importShipmentPayload($inactive, ['sticker_price' => 300, 'items' => []]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sticker_price', 'port_id', 'items']);
});

test('shipment list filters and stats aggregate correctly', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $nasib = Port::factory()->create(['name' => 'Nasib']);
    $latakia = Port::factory()->create(['name' => 'Latakia']);

    $first = ImportShipment::factory()->create(['port_id' => $nasib->id, 'stickers_count' => 2, 'sticker_price' => 200, 'merchant_name' => 'Alpha', 'release_decision' => 'done']);
    ImportShipmentItem::factory()->create(['import_shipment_id' => $first->id, 'country' => 'Germany', 'quantity' => 4, 'total_price' => 100]);

    $second = ImportShipment::factory()->create(['port_id' => $latakia->id, 'stickers_count' => 3, 'sticker_price' => 400, 'merchant_name' => 'Beta', 'release_decision' => null]);
    ImportShipmentItem::factory()->create(['import_shipment_id' => $second->id, 'country' => 'Turkey', 'quantity' => 6, 'total_price' => 250]);

    $this->getJson('/api/admin/import-shipments?port_id='.$nasib->id)->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.merchant_name', 'Alpha');
    $this->getJson('/api/admin/import-shipments?search=Beta')->assertJsonPath('meta.total', 1);
    $this->getJson('/api/admin/import-shipments?country=Germ')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.merchant_name', 'Alpha');
    $this->getJson('/api/admin/import-shipments?sticker_price=400')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.merchant_name', 'Beta');
    $this->getJson('/api/admin/import-shipments?released=1')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.merchant_name', 'Alpha');
    $this->getJson('/api/admin/import-shipments?released=0')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.merchant_name', 'Beta');

    $stats = $this->getJson('/api/admin/import-shipments/stats')->assertOk();
    $stats->assertJsonPath('data.totals.shipments_count', 2)
        ->assertJsonPath('data.totals.stickers_count', 5)
        ->assertJsonPath('data.totals.stickers_total', 1600)
        ->assertJsonPath('data.totals.items_count', 2)
        ->assertJsonPath('data.totals.total_quantity', 10)
        ->assertJsonPath('data.totals.released_count', 1)
        ->assertJsonCount(2, 'data.by_port')
        ->assertJsonPath('data.by_country.0.country', 'Turkey')
        ->assertJsonCount(12, 'data.monthly');

    expect((float) $stats->json('data.totals.total_value'))->toBe(350.0);

    $this->getJson('/api/admin/import-shipments/stats?port_id='.$latakia->id)
        ->assertJsonPath('data.totals.shipments_count', 1)
        ->assertJsonPath('data.totals.stickers_total', 1200);
});

test('admin manages ports and cannot delete a port in use', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $portId = $this->postJson('/api/admin/ports', ['name' => 'New Crossing'])->assertCreated()->json('data.id');
    $this->putJson("/api/admin/ports/{$portId}", ['name' => 'New Crossing 2', 'is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
    $this->postJson('/api/admin/ports', ['name' => 'New Crossing 2'])->assertUnprocessable()->assertJsonValidationErrors(['name']);

    $used = Port::factory()->create();
    ImportShipment::factory()->create(['port_id' => $used->id]);
    $this->deleteJson("/api/admin/ports/{$used->id}")->assertUnprocessable();
    $this->deleteJson("/api/admin/ports/{$portId}")->assertOk();
});

test('non-admins cannot write ports and only see active ports', function () {
    Port::factory()->inactive()->create(['name' => 'Closed']);
    Sanctum::actingAs(User::factory()->employee()->create());

    $this->postJson('/api/employee/ports', ['name' => 'X'])->assertMethodNotAllowed();
    $this->postJson('/api/admin/ports', ['name' => 'X'])->assertForbidden();

    $names = collect($this->getJson('/api/employee/ports')->assertOk()->json('data'))->pluck('name');
    expect($names)->not->toContain('Closed');

    $this->getJson('/api/employee/import-shipments/options')
        ->assertOk()
        ->assertJsonPath('data.can_pick_syndicate', true);
});

test('shipment workspace pages render for each role', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/shipments')->assertOk()->assertInertia(fn ($page) => $page->component('Shipments/Index')->where('workspace', 'admin'));
    $this->get('/admin/ports')->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Ports/Index'));

    $this->actingAs(User::factory()->employee()->create())
        ->get('/employee/shipments/create')->assertOk()->assertInertia(fn ($page) => $page->component('Shipments/Form')->where('workspace', 'employee'));

    $this->actingAs(syndicateUserWithProfile())
        ->get('/syndicate/shipments/5')->assertOk()->assertInertia(fn ($page) => $page->component('Shipments/Show')->where('shipmentId', 5));
});
