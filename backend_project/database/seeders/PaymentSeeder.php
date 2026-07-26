<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $invoices = Invoice::where('total_amount', '>', 0)->get();

        if ($invoices->isEmpty()) {
            $this->command->warn('لا يوجد فواتير بمبلغ إجمالي أكبر من صفر، يرجى تشغيل InvoiceSeeder أولاً.');
            return;
        }

        $staffUser = User::find(3,'id');

        if (!$staffUser) {
            $this->command->warn('لا يوجد مستخدم بدور secretary/admin لاستخدامه كـ received_by، يرجى إضافة واحد.');
            return;
        }

        foreach ($invoices as $index => $invoice) {
            // تصفير أي دفعات سابقة لتفادي التكرار عند إعادة تشغيل السيدر
            $invoice->payments()->delete();

            $scenario = $index % 3; // 0 = غير مدفوع, 1 = جزئي, 2 = مدفوع بالكامل

            $totalPaid = 0.0;

            if ($scenario === 1) {
                // دفعة جزئية واحدة أو دفعتان تصل تقريباً إلى 50-70% من الإجمالي
                $firstPayment = round($invoice->total_amount * 0.4, 2);

                Payment::create([
                    'invoice_id'     => $invoice->id,
                    'received_by'    => $staffUser->id,
                    'amount'         => $firstPayment,
                    'payment_method' => 'cash',
                ]);

                $totalPaid = $firstPayment;

                // نصف الفواتير الجزئية تحصل على دفعة ثانية أيضاً
                if ($invoice->id % 2 === 0) {
                    $secondPayment = round($invoice->total_amount * 0.25, 2);

                    Payment::create([
                        'invoice_id'     => $invoice->id,
                        'received_by'    => $staffUser->id,
                        'amount'         => $secondPayment,
                        'payment_method' => 'card',
                    ]);

                    $totalPaid += $secondPayment;
                }
            } elseif ($scenario === 2) {
                // دفعة كاملة، أحياناً على دفعتين
                if ($invoice->id % 2 === 0) {
                    $firstPayment = round($invoice->total_amount * 0.5, 2);
                    $secondPayment = round($invoice->total_amount - $firstPayment, 2);

                    Payment::create([
                        'invoice_id'     => $invoice->id,
                        'received_by'    => $staffUser->id,
                        'amount'         => $firstPayment,
                        'payment_method' => 'cash',
                    ]);

                    Payment::create([
                        'invoice_id'     => $invoice->id,
                        'received_by'    => $staffUser->id,
                        'amount'         => $secondPayment,
                        'payment_method' => 'cash',
                    ]);

                    $totalPaid = $firstPayment + $secondPayment;
                } else {
                    Payment::create([
                        'invoice_id'     => $invoice->id,
                        'received_by'    => $staffUser->id,
                        'amount'         => $invoice->total_amount,
                        'payment_method' => 'card',
                    ]);

                    $totalPaid = (float) $invoice->total_amount;
                }
            }
            // scenario === 0: لا يوجد دفعات، يبقى غير مدفوع

            $invoice->update(['paid_amount' => $totalPaid]);
            $invoice->recalculate();
        }

    }
}
