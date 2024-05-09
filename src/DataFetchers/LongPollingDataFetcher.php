<?php

namespace SequentSoft\ThreadFlowTelegram\DataFetchers;

use Closure;
use Exception;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class LongPollingDataFetcher implements DataFetcherInterface
{
    protected array $beforeFetchHandlers = [];

    protected array $afterFetchHandlers = [];

    protected array $fetchErrorHandlers = [];

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected int $timeout = 30,
        protected int $maxAttempts = 3,
        protected int $attemptDelay = 1,
    ) {
    }

    public function beforeFetch(Closure $handler): void
    {
        $this->beforeFetchHandlers[] = $handler;
    }

    public function afterFetch(Closure $handler): void
    {
        $this->afterFetchHandlers[] = $handler;
    }

    public function onFetchError(Closure $handler): void
    {
        $this->fetchErrorHandlers[] = $handler;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    protected function fetchUpdates(
        int $offset,
        int $maxAttempts,
        int $attemptDelay
    ): string {
        $attempt = 0;
        $exception = null;

        while ($attempt < $maxAttempts) {
            foreach ($this->beforeFetchHandlers as $handler) {
                $handler($attempt);
            }

            try {
                return $this->httpClient->postJson('getUpdates', [
                    'offset' => $offset,
                    'timeout' => $this->timeout,
                ])->getRawData();
            } catch (Exception $e) {
                $exception = $e;
                foreach ($this->fetchErrorHandlers as $handler) {
                    $handler($e, $attempt);
                }
                $attempt++;
                sleep($attemptDelay);
            }
        }

        if ($exception) {
            throw $exception;
        }

        return '';
    }

    public function fetch(Closure $handleUpdate): never
    {
        $offset = 0;

        // @phpstan-ignore-next-line
        while (true) {
            $updates = $this->fetchUpdates(
                $offset,
                $this->maxAttempts,
                $this->attemptDelay
            );

            foreach ($this->afterFetchHandlers as $handler) {
                $handler($updates);
            }

            $parsedUpdates = json_decode(
                $updates,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            foreach ($parsedUpdates['result'] as $update) {
                $handleUpdate($update);
                $offset = $update['update_id'] + 1;
            }
        }
    }
}
