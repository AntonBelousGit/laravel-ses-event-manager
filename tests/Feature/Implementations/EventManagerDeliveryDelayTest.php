<?php

namespace AntonBelousGit\LaravelSesEventManager\Tests\Feature\Implementations;

use AntonBelousGit\LaravelSesEventManager\App\Models\Email;
use AntonBelousGit\LaravelSesEventManager\Tests\FeatureTestCase;
use Illuminate\Routing\Router;

class EventManagerDeliveryDelayTest extends FeatureTestCase
{
    protected array $tables;
    protected Router $router;
    protected string $routeName;
    protected string $emailTable;
    protected string $delayTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->app->make(Router::class);
        $this->routeName = $this->app->config->get('laravel-ses-event-manager.named_route_prefix').'.delivery_delays';

        // Import the tables from the migration
        $this->tables = [];
        $this->tables[] = include __DIR__.'/../../../database/migrations/create_emails_table.php.stub';
        $this->tables[] = include __DIR__.'/../../../database/migrations/create_email_delivery_delays_table.php.stub';
        $this->tables[] = include __DIR__.'/../../../database/migrations/add_subject_to_emails_table.php.stub';
        $this->emailTable = config('laravel-ses-event-manager.database_name_prefix').'_emails';
        $this->delayTable = config('laravel-ses-event-manager.database_name_prefix').'_email_delivery_delays';

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
    public function deliveryDelayEventIsSuccessfullySaved()
    {
        $this->assertDatabaseCount($this->delayTable, 0);
        $this->assertDatabaseCount($this->emailTable, 0);

        $email = Email::factory()->create();

        $this->assertModelExists($email);
        $this->assertDatabaseCount($this->emailTable, 1);
        $this->assertDatabaseHas($this->emailTable, [
            'has_delivery_delay' => false,
        ]);

        $route = $this->router->getRoutes()->getByName($this->routeName);
        $fakeJson = json_decode($this->payload);
        $fakeJson->Message->mail->messageId = $email->message_id;
        $fakeJson->Message = json_encode($fakeJson->Message);

        $this->json(
            'POST',
            "$route->uri",
            (array) $fakeJson
        )
        ->assertJson(['success' => true])
        ->assertStatus(200);

        $this->assertDatabaseCount($this->delayTable, 1);
        $this->assertDatabaseCount($this->emailTable, 1);
        $this->assertDatabaseHas($this->emailTable, [
            'has_delivery_delay' => true,
        ]);
        $this->assertModelExists($email->deliveryDelay);
    }

    protected string $payload = '
    {
        "Type" : "Notification",
        "MessageId" : "22b80b92-fdea-4c2c-8f9d-bdfb0c7bf324",
        "TopicArn" : "arn:aws:sns:us-west-2:123456789012:MyTopic",
        "Subject" : "My First Message",
        "Timestamp" : "2012-05-02T00:54:06.655Z",
        "SignatureVersion" : "1",
        "Signature" : "EXAMPLEw6JRN...",
        "SigningCertURL" : "https://sns.us-west-2.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem",
        "UnsubscribeURL" : "https://sns.us-west-2.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-west-2:123456789012:MyTopic:c9135db0-26c4-47ec-8998-413945fb5a96",
        "Message" : {
            "eventType": "DeliveryDelay",
            "mail":{
                "timestamp":"2020-06-16T00:15:40.641Z",
                "source":"sender@example.com",
                "sourceArn":"arn:aws:ses:us-east-1:123456789012:identity/sender@example.com",
                "sendingAccountId":"123456789012",
                "messageId":"EXAMPLE7c191be45-e9aedb9a-02f9-4d12-a87d-dd0099a07f8a-000000",
                "destination":[
                    "recipient@example.com"
                ],
                "headersTruncated":false,
                "tags":{
                    "ses:configuration-set":[
                        "ConfigSet"
                    ]
                }
            },
            "deliveryDelay": {
                "timestamp": "2020-06-16T00:25:40.095Z",
                "delayType": "TransientCommunicationFailure",
                "expirationTime": "2020-06-16T00:25:40.914Z",
                "delayedRecipients": [{
                    "emailAddress": "recipient@example.com",
                    "status": "4.4.1",
                    "diagnosticCode": "smtp; 421 4.4.1 Unable to connect to remote host"
                }]
            }
        }
    }';
}
