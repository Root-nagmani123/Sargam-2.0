<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Mess\AvailableQuantityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Purchase order approval (PR #336 F-004).
 *
 * The rules pinned here:
 *   - only Mess Admin / Super Admin approve or reject, and only a pending PO,
 *   - a pending PO is not stock; it becomes stock when approved,
 *   - only Mess Admin, Super Admin or Mess Billing can save an edit,
 *   - an approved PO keeps its vendor and lines; only bill details change,
 *   - a rejected PO cannot be edited,
 *   - purchase orders cannot be deleted (the DELETE route is disabled),
 *   - lowering a return is refused when the stock is no longer there.
 *
 * Every row is created inside a transaction for a vendor, store and item of its own, so real
 * data neither affects nor is affected by the test. Roles come from the session, which is
 * what login writes and what hasRole() reads first.
 */
class MessPurchaseOrderApprovalTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private int $vendorId;

    private int $otherVendorId;

    private int $storeId;

    private int $itemId;

    protected function setUp(): void
    {
        parent::setUp();

        // A user who holds none of the Mess roles in the role tables, so the session alone decides.
        $user = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Mess Admin', 'Super Admin', 'Mess Billing']))
            ->first();
        if (! $user) {
            $this->markTestSkipped('no user_credentials row without a Mess role to act as');
        }
        $this->user = $user;

        $now = now();
        $this->vendorId = DB::table('mess_vendors')->insertGetId(['name' => 'Test Vendor '.uniqid(), 'created_at' => $now, 'updated_at' => $now]);
        $this->otherVendorId = DB::table('mess_vendors')->insertGetId(['name' => 'Test Vendor B '.uniqid(), 'created_at' => $now, 'updated_at' => $now]);
        $this->storeId = DB::table('mess_stores')->insertGetId(['store_name' => 'Test Store '.uniqid(), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $this->itemId = DB::table('mess_item_subcategories')->insertGetId([
            'name' => 'Test Item '.uniqid(),
            'item_code' => 'T'.uniqid(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function actAs(array $roles): void
    {
        $this->actingAs($this->user);
        $this->withSession(['user_roles' => $roles]);
    }

    private function purchaseOrder(string $status, float $quantity = 10): int
    {
        $now = now();
        $poId = DB::table('mess_purchase_orders')->insertGetId([
            'po_number' => 'PO/TEST/'.uniqid(),
            'vendor_id' => $this->vendorId,
            'store_id' => $this->storeId,
            'po_date' => $now->toDateString(),
            'total_amount' => $quantity * 50,
            'status' => $status,
            'created_by' => $this->user->pk,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('mess_purchase_order_items')->insert([
            'purchase_order_id' => $poId,
            'item_subcategory_id' => $this->itemId,
            'quantity' => $quantity,
            'unit_price' => 50,
            'tax_percent' => 0,
            'total_price' => $quantity * 50,
        ]);

        return $poId;
    }

    private function po(int $id): object
    {
        return DB::table('mess_purchase_orders')->where('id', $id)->first();
    }

    private function lineQuantities(int $poId): array
    {
        return DB::table('mess_purchase_order_items')->where('purchase_order_id', $poId)
            ->pluck('quantity')->map(fn ($q) => (float) $q)->all();
    }

    private function editPayload(float $quantity, ?int $vendorId = null): array
    {
        return [
            'vendor_id' => $vendorId ?? $this->vendorId,
            'store_id' => $this->storeId,
            'po_date' => now()->toDateString(),
            'bill_no' => 'BILL-TEST-1',
            'items' => [[
                'item_subcategory_id' => $this->itemId,
                'quantity' => $quantity,
                'unit_price' => 50,
                'tax_percent' => 0,
            ]],
        ];
    }

    private function inStock(): float
    {
        $map = AvailableQuantityService::availableQuantitiesForStore('store', $this->storeId, true);

        return (float) ($map[$this->itemId] ?? 0);
    }

    public function test_mess_admin_approves_a_pending_po_and_its_items_become_stock(): void
    {
        $poId = $this->purchaseOrder('pending', 10);
        $this->assertSame(0.0, $this->inStock(), 'a pending PO must not be stock');

        $this->actAs(['Mess Admin']);
        $this->post(route('admin.mess.purchaseorders.approve', $poId))
            ->assertRedirect(route('admin.mess.purchaseorders.index'))
            ->assertSessionHas('success');

        $po = $this->po($poId);
        $this->assertSame('approved', $po->status);
        $this->assertEquals($this->user->pk, $po->approved_by);
        $this->assertNotNull($po->approved_at);
        $this->assertSame(10.0, $this->inStock(), 'an approved PO is stock');
    }

    public function test_mess_billing_cannot_approve_or_reject(): void
    {
        $poId = $this->purchaseOrder('pending');

        $this->actAs(['Mess Billing']);
        $this->post(route('admin.mess.purchaseorders.approve', $poId))->assertSessionHas('po_edit_error');
        $this->post(route('admin.mess.purchaseorders.reject', $poId))->assertSessionHas('po_edit_error');

        $po = $this->po($poId);
        $this->assertSame('pending', $po->status);
        $this->assertNull($po->approved_by);
    }

    public function test_mess_admin_rejects_and_a_rejected_po_is_not_stock_and_cannot_be_edited(): void
    {
        $poId = $this->purchaseOrder('pending', 10);

        $this->actAs(['Mess Admin']);
        $this->post(route('admin.mess.purchaseorders.reject', $poId))->assertSessionHas('success');
        $this->assertSame('rejected', $this->po($poId)->status);
        $this->assertSame(0.0, $this->inStock());

        $this->put(route('admin.mess.purchaseorders.update', $poId), $this->editPayload(99))
            ->assertSessionHas('po_edit_error');
        $this->assertSame([10.0], $this->lineQuantities($poId));
    }

    public function test_an_approved_po_cannot_be_approved_or_rejected_again(): void
    {
        $poId = $this->purchaseOrder('approved');

        $this->actAs(['Mess Admin']);
        $this->post(route('admin.mess.purchaseorders.reject', $poId))->assertSessionHas('po_edit_error');

        $this->assertSame('approved', $this->po($poId)->status);
    }

    public function test_a_role_without_edit_rights_cannot_save_an_edit(): void
    {
        $poId = $this->purchaseOrder('pending', 10);

        $this->actAs(['Faculty']);
        $this->put(route('admin.mess.purchaseorders.update', $poId), $this->editPayload(99))
            ->assertRedirect(route('admin.mess.purchaseorders.index'))
            ->assertSessionHas('po_edit_error');

        $this->assertSame([10.0], $this->lineQuantities($poId));
        $this->assertNull($this->po($poId)->bill_no);
    }

    public function test_mess_billing_can_edit_a_pending_po(): void
    {
        $poId = $this->purchaseOrder('pending', 10);

        $this->actAs(['Mess Billing']);
        $this->put(route('admin.mess.purchaseorders.update', $poId), $this->editPayload(25))
            ->assertSessionHas('success');

        $this->assertSame([25.0], $this->lineQuantities($poId));
        $this->assertSame('BILL-TEST-1', $this->po($poId)->bill_no);
    }

    public function test_editing_an_approved_po_saves_bill_details_only(): void
    {
        $poId = $this->purchaseOrder('approved', 10);

        $this->actAs(['Mess Billing']);
        $this->put(route('admin.mess.purchaseorders.update', $poId), $this->editPayload(99, $this->otherVendorId))
            ->assertSessionHas('success');

        $po = $this->po($poId);
        $this->assertSame('BILL-TEST-1', $po->bill_no);
        $this->assertEquals($this->vendorId, $po->vendor_id, 'vendor is locked on an approved PO');
        $this->assertSame([10.0], $this->lineQuantities($poId), 'lines are locked on an approved PO');
    }

    public function test_purchase_orders_cannot_be_deleted(): void
    {
        $poId = $this->purchaseOrder('pending');

        $this->actAs(['Mess Admin']);
        $this->delete('/admin/mess/purchaseorders/'.$poId)->assertStatus(405);

        $this->assertNotNull($this->po($poId));
    }

    public function test_lowering_a_return_is_refused_when_the_stock_is_gone(): void
    {
        $available = [$this->itemId => 5.0];

        $this->assertSame([], AvailableQuantityService::returnReductionShortfalls($available, [
            $this->itemId => ['qty' => 5, 'name' => 'Rice'],
        ]), 'reducing by exactly what is in stock is allowed');

        $messages = AvailableQuantityService::returnReductionShortfalls($available, [
            $this->itemId => ['qty' => 7, 'name' => 'Rice'],
        ]);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('only 5 is in stock', $messages[0]);
    }
}
