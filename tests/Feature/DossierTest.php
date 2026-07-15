<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Dossier;
use App\Dto\PaymentNotification;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;
use App\Events\Afbetaald;
use App\Events\Deelbetaald;

class DossierTest extends TestCase
{
    use RefreshDatabase;

    public function test_dossier_ineens_afbetaald(): void
    {
        // arrange
        // TODO: dit naar factory
        Dossier::create([
            'id' => 1,
            'claim' => -10000,
            'saldo' => -10000,
        ]);
        $dossier = Dossier::find(1);
        $paymentNotification = new PaymentNotification(dossierId: 1, amount: 10000, paymentDate: new Carbon('1 september 2014'));
        Event::fake();
        // act
        $answer = $dossier->acceptPayment($paymentNotification);
        // assert
        Event::assertDispatchedOnce(Afbetaald::class);
        $this->assertTrue($dossier->closed());
    }

}
