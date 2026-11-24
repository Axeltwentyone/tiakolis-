<?php

namespace Tests\Feature;

use App\Enums\Statut;
use App\Mail\PaiementValide;
use App\Models\Paiement;
use App\Models\Precommande;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class JekoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.jeko' => [
            'url' => 'https://api.jeko.africa', 'api_key' => 'cle', 'api_key_id' => 'id-cle', 'store_id' => 'magasin-1',
            'webhook_secret' => 'secret-webhook', 'moyens' => ['orange', 'wave', 'mtn', 'moov'],
        ]]);
        Mail::fake();
    }

    private function stock(string $taille = 'M'): int
    {
        return Stock::whereHas('produit', fn ($q) => $q->where('slug', 'melo-noir'))->where('taille', $taille)->value('quantite');
    }

    private function payer(string $moyen = 'orange', int $quantite = 2): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/paiements/jeko', $this->client() + ['moyen' => $moyen, 'articles' => [['piece' => 'melo-noir', 'taille' => 'M', 'quantite' => $quantite]]]);
    }

    private function webhook(array $transaction, ?string $signature = null): \Illuminate\Testing\TestResponse
    {
        $corps = json_encode($transaction);

        return $this->call('POST', '/api/jeko/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_JEKO_SIGNATURE' => $signature ?? hash_hmac('sha256', $corps, 'secret-webhook'), 'HTTP_JEKO_EVENT' => 'TRANSACTION_COMPLETED',
        ], $corps);
    }

    public function test_le_catalogue_propose_les_moyens_jeko_seulement_si_les_cles_sont_la(): void
    {
        $this->getJson('/api/catalogue')->assertJsonPath('paiement.jeko', ['orange', 'wave', 'mtn', 'moov']);
        config(['services.jeko.api_key' => null]);
        $this->getJson('/api/catalogue')->assertJsonPath('paiement.jeko', []);
        $this->payer()->assertNotFound();
    }

    public function test_le_client_est_envoye_sur_la_page_jeko_sans_rien_reserver(): void
    {
        Http::fake(['api.jeko.africa/partner_api/payment_requests' => Http::response(['id' => 'pr-123', 'status' => 'pending', 'redirectUrl' => 'https://pay.jeko.africa/pay_request/pr/pr-123'])]);

        $r = $this->payer()->assertOk()->assertJsonPath('redirectUrl', 'https://pay.jeko.africa/pay_request/pr/pr-123');

        Http::assertSent(fn (RequeteHttp $q) => $q->hasHeader('X-API-KEY', 'cle') && $q->hasHeader('X-API-KEY-ID', 'id-cle')
            && $q['storeId'] === 'magasin-1' && $q['amountCents'] === 2500000 && $q['currency'] === 'XOF'
            && $q['reference'] === $r->json('reference') && $q['paymentDetails']['data']['paymentMethod'] === 'orange'
            && str_ends_with($q['paymentDetails']['data']['successUrl'], '/paiement/retour/'.$r->json('reference')));
        $this->assertSame(0, Precommande::count()); // pas de précommande tant que ce n'est pas payé
        $this->assertSame(40, $this->stock());
        $this->assertSame('pr-123', Paiement::first()->externe_id);
    }

    public function test_le_webhook_signe_cree_la_precommande_payee_une_seule_fois(): void
    {
        Http::fake(['api.jeko.africa/*' => Http::response(['id' => 'pr-123', 'status' => 'pending', 'redirectUrl' => 'https://pay.jeko.africa/x'])]);
        $ref = $this->payer()->json('reference');
        $transaction = ['id' => 'tr-9', 'status' => 'success', 'transactionType' => 'payment', 'paymentMethod' => 'orange',
            'transactionDetails' => ['id' => 'pr-123', 'reference' => $ref]];

        $this->webhook($transaction)->assertOk();
        $this->webhook($transaction)->assertOk(); // Jeko peut renvoyer : pas de doublon

        $p = Precommande::sole();
        $this->assertSame($ref, $p->reference);
        $this->assertSame(Statut::Payee, $p->statut);
        $this->assertSame('jeko · orange', $p->paiement);
        $this->assertSame(25000, $p->total);
        $this->assertSame(38, $this->stock());
        Mail::assertSent(PaiementValide::class, fn ($m) => $m->hasTo('favor@example.test'));
    }

    public function test_un_webhook_mal_signe_est_refuse(): void
    {
        Http::fake(['api.jeko.africa/*' => Http::response(['id' => 'pr-123', 'redirectUrl' => 'https://pay.jeko.africa/x'])]);
        $ref = $this->payer()->json('reference');

        $this->webhook(['status' => 'success', 'transactionDetails' => ['id' => 'pr-123', 'reference' => $ref]], 'fausse-signature')->assertUnauthorized();
        $this->assertSame(0, Precommande::count());
    }

    public function test_au_retour_le_site_verifie_le_paiement_sans_attendre_le_webhook(): void
    {
        Http::fake([
            'api.jeko.africa/partner_api/payment_requests' => Http::response(['id' => 'pr-123', 'redirectUrl' => 'https://pay.jeko.africa/x']),
            'api.jeko.africa/partner_api/payment_requests/pr-123' => Http::response(['id' => 'pr-123', 'status' => 'success', 'transaction' => ['id' => 'tr-9']]),
        ]);
        $ref = $this->payer('wave', 1)->json('reference');

        $this->get("/paiement/retour/{$ref}")->assertOk()->assertSee('Merci,')->assertSee($ref);
        $this->assertSame(Statut::Payee, Precommande::sole()->statut);
    }

    public function test_paiement_echoue_aucune_precommande(): void
    {
        Http::fake([
            'api.jeko.africa/partner_api/payment_requests' => Http::response(['id' => 'pr-123', 'redirectUrl' => 'https://pay.jeko.africa/x']),
            'api.jeko.africa/partner_api/payment_requests/pr-123' => Http::response(['id' => 'pr-123', 'status' => 'error']),
        ]);
        $ref = $this->payer()->json('reference');

        $this->get("/paiement/retour/{$ref}?echec=1")->assertOk()->assertSee("Le paiement n'est pas passé.", false);
        $this->assertSame(0, Precommande::count());
        $this->assertSame(40, $this->stock());
    }

    public function test_paye_alors_que_le_stock_est_parti_la_commande_reste_a_verifier(): void
    {
        Http::fake(['api.jeko.africa/*' => Http::response(['id' => 'pr-123', 'redirectUrl' => 'https://pay.jeko.africa/x'])]);
        $ref = $this->payer()->json('reference');
        Stock::whereHas('produit', fn ($q) => $q->where('slug', 'melo-noir'))->where('taille', 'M')->update(['quantite' => 1]);

        $this->webhook(['id' => 'tr-9', 'status' => 'success', 'transactionType' => 'payment', 'transactionDetails' => ['id' => 'pr-123', 'reference' => $ref]])->assertOk();

        $p = Precommande::sole();
        $this->assertSame(Statut::AVerifier, $p->statut); // l'équipe décide : autre taille ou remboursement
        $this->assertStringContainsString('Stock insuffisant', $p->note);
        Mail::assertNotSent(PaiementValide::class);
    }

    public function test_jeko_ne_repond_pas(): void
    {
        Http::fake(['api.jeko.africa/*' => Http::response(['message' => 'erreur'], 500)]);
        $this->payer()->assertStatus(502)->assertJsonPath('erreur', fn ($e) => str_contains($e, 'ne répond pas'));
        $this->assertSame('echec', Paiement::first()->statut);
    }
}
