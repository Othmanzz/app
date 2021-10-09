<?php
namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements FromCollection , WithHeadings
{
    use Exportable;
    public function __construct($type)
    {
        $this->type = $type;
    }
    public function collection()
    {
        return User::query()->where('type','=',$this->type)->get(['id','name' ,'email','phone','phone2','created_at']);
    }
    public function headings(): array
    {
        return ["الرقم", "اسم العميل", "البريد الالكتروني" ," الهاتف","الهاتف الاخر"," التاريخ"];
    }
}
