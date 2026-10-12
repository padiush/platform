<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/** Pages render in the browser; the server never asks anything to pre-render them. */
class InertiaRenderingTest extends TestCase
{
    public function test_a_page_is_not_sent_to_the_dev_server_to_render(): void
    {
        $hot = tempnam(sys_get_temp_dir(), 'hot');
        file_put_contents($hot, 'http://vite.test:5173');
        Vite::useHotFile($hot);
        Http::fake();

        try {
            $this->get(route('login'))->assertOk();
        } finally {
            unlink($hot);
        }

        Http::assertNothingSent();
    }
}
