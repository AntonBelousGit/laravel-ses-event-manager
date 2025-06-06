<?php

namespace AntonBelousGit\LaravelSesEventManager\Mocking;

use AntonBelousGit\LaravelSesEventManager\LaravelSesEventManagerServiceProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TestMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view(Str::studly(LaravelSesEventManagerServiceProvider::PREFIX).'::test');
    }
}
