<?php
namespace App\Exports;

use App\Invoice;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderExport implements FromCollection , WithHeadings
{
    use Exportable;

    public function collection()
    {
        return Order::query()->get(['id','name' ,'total','payment_method','currency','created_at']);
    }
    public function headings(): array
    {
        return ["الرقم", "اسم العميل", "الاجمالي" ,"طريقة الدفع","العملة","التاريخ"];
    }
}
