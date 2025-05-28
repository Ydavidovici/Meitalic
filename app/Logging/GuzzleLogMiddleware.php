<?php
// app/Logging/GuzzleLogMiddleware.php

namespace App\Logging;

use Closure;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleLogMiddleware
{
    protected string $channel;

    public function __construct(string $channel = 'api')
    {
        $this->channel = $channel;
    }

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $start = microtime(true);

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request, $start) {
                    $duration = round((microtime(true) - $start) * 1000, 2);

                    // Log the outgoing request
                    Log::channel($this->channel)->info('HTTP ▶️ Request', [
                        'method'    => $request->getMethod(),
                        'url'       => (string) $request->getUri(),
                        'headers'   => $request->getHeaders(),
                        'body'      => (string) $request->getBody(),
                        'timestamp' => now()->toIso8601String(),
                    ]);

                    // Log the incoming response
                    Log::channel($this->channel)->info('HTTP ◀️ Response', [
                        'status'    => $response->getStatusCode(),
                        'headers'   => $response->getHeaders(),
                        'body'      => (string) $response->getBody(),
                        'duration'  => "{$duration} ms",
                        'timestamp' => now()->toIso8601String(),
                    ]);

                    return $response;
                }
            );
        };
    }
}
