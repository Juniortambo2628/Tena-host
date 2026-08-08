<?php

use App\Models\MpesaTransaction;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| SubscriptionService
|--------------------------------------------------------------------------
*/

test('activateForUser creates subscription and transaction', function () {
    Mail::fake();

    $user = User::factory()->create([
        'phone_number' => '+254712345678',
        'email' => 'test@example.com',
    ]);

    $service = new SubscriptionService();
    $transaction = $service->activateForUser($user, 'mpesa', 'REF123', 1500.00);

    expect($transaction)->toBeInstanceOf(MpesaTransaction::class)
        ->and($transaction->user_id)->toBe($user->id)
        ->and($transaction->Amount)->toBe(1500.00)
        ->and($transaction->Status)->toBe('completed');

    expect($user->fresh()->subscribed('default'))->toBeTrue();
});

test('activateForUser sends receipt email', function () {
    Mail::fake();

    $user = User::factory()->create([
        'phone_number' => '+254712345678',
        'email' => 'receipt@example.com',
    ]);

    $service = new SubscriptionService();
    $service->activateForUser($user, 'mpesa', 'REF456', 2000.00);

    Mail::assertSent(\App\Mail\PaymentReceiptMail::class, function ($mail) {
        return $mail->hasTo('receipt@example.com');
    });
});

test('activateForUser does not create duplicate subscription', function () {
    Mail::fake();

    $user = User::factory()->create([
        'phone_number' => '+254712345678',
        'email' => 'test@example.com',
    ]);

    $service = new SubscriptionService();

    $service->activateForUser($user, 'mpesa', 'REF789', 1000.00);
    $service->activateForUser($user->fresh(), 'mpesa', 'REF012', 1000.00);

    $subscriptions = $user->subscriptions()->where('type', 'default')->get();

    expect($subscriptions)->toHaveCount(1);
});

test('sendReceipt does not throw when user has no email', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => '']);
    $transaction = MpesaTransaction::create([
        'user_id' => $user->id,
        'MerchantRequestID' => 'test_'.time(),
        'CheckoutRequestID' => 'test_ref',
        'Amount' => 1000,
        'PhoneNumber' => '+254712345678',
        'Status' => 'completed',
        'ResultDesc' => 'Test payment',
    ]);

    $service = new SubscriptionService();
    $service->sendReceipt($user, $transaction);

    Mail::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| HasImageUpload Trait
|--------------------------------------------------------------------------
*/

test('storeImage stores file and returns path', function () {
    Storage::fake('public');

    $class = new class
    {
        use \App\Traits\HasImageUpload;

        public function testStore(UploadedFile $file, string $dir, string $disk = 'public'): string
        {
            return $this->storeImage($file, $dir, $disk);
        }
    };

    $file = UploadedFile::fake()->image('avatar.jpg');
    $path = $class->testStore($file, 'avatars');

    expect($path)->toStartWith('/storage/avatars/')
        ->and(Storage::disk('public')->exists(str_replace('/storage/', '', $path)))->toBeTrue();
});

test('updateImage deletes old file and stores new', function () {
    Storage::fake('public');

    $class = new class
    {
        use \App\Traits\HasImageUpload;

        public function testUpdate(?UploadedFile $file, ?string $currentPath, string $dir, string $disk = 'public'): ?string
        {
            return $this->updateImage($file, $currentPath, $dir, $disk);
        }
    };

    $oldFile = UploadedFile::fake()->image('old.jpg');
    $oldPath = $class->testUpdate($oldFile, null, 'logos');
    $relativeOld = str_replace('/storage/', '', $oldPath);

    expect(Storage::disk('public')->exists($relativeOld))->toBeTrue();

    $newFile = UploadedFile::fake()->image('new.jpg');
    $newPath = $class->testUpdate($newFile, $oldPath, 'logos');

    expect(Storage::disk('public')->exists($relativeOld))->toBeFalse()
        ->and(Storage::disk('public')->exists(str_replace('/storage/', '', $newPath)))->toBeTrue()
        ->and($newPath)->not->toBe($oldPath);
});

test('updateImage returns current path when no new file', function () {
    Storage::fake('public');

    $class = new class
    {
        use \App\Traits\HasImageUpload;

        public function testUpdate(?UploadedFile $file, ?string $currentPath, string $dir, string $disk = 'public'): ?string
        {
            return $this->updateImage($file, $currentPath, $dir, $disk);
        }
    };

    $currentPath = '/storage/logos/existing.png';

    $result = $class->testUpdate(null, $currentPath, 'logos');

    expect($result)->toBe($currentPath);
});
