<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Dto\PaymentNotification;
use App\Events\Afbetaald;
use App\Events\Deelbetaald;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;

class Dossier extends Model
{
    protected $fillable = ['id', 'claim', 'saldo'];

    public function acceptPayment(PaymentNotification $paymentNotification) {
        DB::transaction(function () use ($paymentNotification) {
            // get saldo and lock table
            $saldo = DB::table('dossiers')
                ->where('id', $paymentNotification->dossierId)
                ->lockForUpdate()
                ->pluck('saldo')
                ->first();
            //insert mutation
            try {
                $mutation_id = DB::table('mutations')->insertGetId([
                    'dossier_id' => $paymentNotification->dossierId,
                    'amount' => $paymentNotification->amount,
                    'payment_date' => $paymentNotification->paymentDate,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                return; // when insert fails, abort the procedure
            }
            //update saldo
            DB::table('dossiers')
                ->where('id', $paymentNotification->dossierId)
                ->update(['saldo' => $saldo + $paymentNotification->amount]);
            //dispatch event
            $this->saldo += $paymentNotification->amount;
            if ($this->closed()) {
                Afbetaald::dispatch($paymentNotification);
            } else {
                Deelbetaald::dispatch($paymentNotification);
            }
        });
    }

    public function closed(): bool {
        return $this->saldo >= 0;
    }
}
