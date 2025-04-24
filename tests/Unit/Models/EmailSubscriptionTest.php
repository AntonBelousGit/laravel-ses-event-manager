<?php

namespace AntonBelousGit\LaravelSesEventManager\Tests\Unit\Models;

use AntonBelousGit\LaravelSesEventManager\App\Models\Email;
use AntonBelousGit\LaravelSesEventManager\App\Models\EmailSubscription;
use AntonBelousGit\LaravelSesEventManager\Tests\UnitTestCase;
use Illuminate\Support\Facades\Schema;

class EmailSubscriptionTest extends UnitTestCase
{
    protected array $tables;

    protected function setUp(): void
    {
        parent::setUp();

        // Import the tables from the migration
        $this->tables = [];
        $this->tables[] = include __DIR__.'/../../../database/migrations/create_emails_table.php.stub';
        $this->tables[] = include __DIR__.'/../../../database/migrations/create_email_subscriptions_table.php.stub';
        $this->tables[] = include __DIR__.'/../../../database/migrations/add_subject_to_emails_table.php.stub';

        foreach ($this->tables as $table) {
            $table->up();
        }
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            $table->down();
        }

        parent::tearDown();
    }

    /** @test */
    public function emailSubscriptionsTableIsCreatedSuccessfully()
    {
        $this->assertTrue(Schema::hasTable(config('laravel-ses-event-manager.database_name_prefix').'_email_subscriptions'));
    }

    /** @test */
    public function emailSubscriptionModelCanBeCreatedSuccessfully()
    {
        $email = Email::factory()->subscribed()->create();
        $emailSubscription = EmailSubscription::factory()->for($email, 'email')->create();

        $this->assertModelExists($email);
        $this->assertModelExists($emailSubscription);
        $this->assertEquals($email->message_id, $emailSubscription->message_id, 'message_ids dont match for the two model instances.');
    }

    /** @test */
    public function modelRelationshipsAreWorking()
    {
        $email = Email::factory()->subscribed()->create();
        $emailSubscription = EmailSubscription::factory()->for($email, 'email')->create();

        $this->assertEquals($email->message_id, $emailSubscription->email->message_id, 'Belongs To object is different from the Parent object.');
        $this->assertEquals($email->subscription->message_id, $emailSubscription->message_id, 'Has One object is different from the Child object.');
    }
}
