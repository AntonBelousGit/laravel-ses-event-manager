<?php

namespace AntonBelousGit\LaravelSesEventManager\Tests\Unit\Models;

use AntonBelousGit\LaravelSesEventManager\App\Models\Email;
use AntonBelousGit\LaravelSesEventManager\App\Models\EmailClick;
use AntonBelousGit\LaravelSesEventManager\Tests\UnitTestCase;
use Illuminate\Support\Facades\Schema;

class EmailClickTest extends UnitTestCase
{
    protected array $tables;

    protected function setUp(): void
    {
        parent::setUp();

        // Import the tables from the migration
        $this->tables = [];
        $this->tables[] = include __DIR__.'/../../../database/migrations/create_emails_table.php.stub';
        $this->tables[] = include __DIR__.'/../../../database/migrations/create_email_clicks_table.php.stub';
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
    public function emailClicksTableIsCreatedSuccessfully()
    {
        $this->assertTrue(Schema::hasTable(config('laravel-ses-event-manager.database_name_prefix').'_email_clicks'));
    }

    /** @test */
    public function emailClickModelCanBeCreatedSuccessfully()
    {
        $email = Email::factory()->clicked()->create();
        $emailClick = EmailClick::factory()->tagged()->for($email, 'email')->create();

        $this->assertModelExists($email);
        $this->assertModelExists($emailClick);
        $this->assertEquals($email->message_id, $emailClick->message_id, 'message_ids dont match for the two model instances.');
    }

    /** @test */
    public function modelRelationshipsAreWorking()
    {
        $email = Email::factory()->clicked()->create();
        $emailClick = EmailClick::factory()->for($email, 'email')->create();

        $this->assertEquals($email->message_id, $emailClick->email->message_id, 'Belongs To object is different from the Parent object.');
        $this->assertEquals($email->clicks->first()->message_id, $emailClick->message_id, 'Has One object is different from the Child object.');
    }
}
