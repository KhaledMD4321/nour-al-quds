<?php

namespace App\Livewire;

use App\Models\Invoice;
use App\Modules\Sales\ReturnService;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SaleReturnForm extends Component
{
    public int    $invoiceId      = 0;
    public array  $returnItems    = [];
    public string $notes          = '';
    public string $errorMessage   = '';
    public string $successMessage = '';
    public ?int   $returnInvoiceId = null;

    // كل item: invoice_item_id, product_name, original_qty,
    //          already_returned, max_returnable, return_qty, unit_price, total

    public function mount(int $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
        $invoice         = Invoice::with('items.product')->findOrFail($invoiceId);

        foreach ($invoice->items as $item) {
            $alreadyReturned = (float) Invoice::where('original_invoice_id', $invoiceId)
                ->where('type', 'sale_return')
                ->where('status', 'confirmed')
                ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->where('invoice_items.product_id', $item->product_id)
                ->sum('invoice_items.quantity');

            $maxReturnable = (float) $item->quantity - $alreadyReturned;

            if ($maxReturnable <= 0) continue; // اتسترجعت كلها مسبقاً

            $this->returnItems[] = [
                'invoice_item_id'  => $item->id,
                'product_name'     => $item->product->name,
                'original_qty'     => (float) $item->quantity,
                'already_returned' => $alreadyReturned,
                'max_returnable'   => $maxReturnable,
                'return_qty'       => 0,
                'unit_price'       => (float) $item->unit_price,
                'total'            => 0,
            ];
        }
    }

    public function updatedReturnItems(): void
    {
        foreach ($this->returnItems as $i => $item) {
            $qty = max(0, min((float) ($item['return_qty'] ?? 0), $item['max_returnable']));
            $this->returnItems[$i]['return_qty'] = $qty;
            $this->returnItems[$i]['total']      = round($qty * $item['unit_price'], 2);
        }
    }

    public function selectAll(): void
    {
        foreach ($this->returnItems as $i => $item) {
            $this->returnItems[$i]['return_qty'] = $item['max_returnable'];
            $this->returnItems[$i]['total']      = round($item['max_returnable'] * $item['unit_price'], 2);
        }
    }

    public function getTotalReturnAmountProperty(): float
    {
        return round(collect($this->returnItems)->sum('total'), 2);
    }

    public function submit(): void
    {
        $this->errorMessage = '';

        $itemsToReturn = collect($this->returnItems)
            ->filter(fn ($i) => (float) ($i['return_qty'] ?? 0) > 0)
            ->map(fn ($i) => [
                'invoice_item_id' => $i['invoice_item_id'],
                'quantity'        => (float) $i['return_qty'],
            ])
            ->values()
            ->toArray();

        if (empty($itemsToReturn)) {
            $this->errorMessage = 'لازم تحدد كمية مرتجعة لصنف واحد على الأقل';
            return;
        }

        try {
            $invoice = Invoice::findOrFail($this->invoiceId);
            $return  = app(ReturnService::class)->createSaleReturn(
                $invoice,
                $itemsToReturn,
                $this->notes ?: null
            );
            $this->returnInvoiceId = $return->id;
            $this->successMessage  = 'تم تسجيل المرتجع — ' . $return->reference_number;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.sale-return-form');
    }
}
