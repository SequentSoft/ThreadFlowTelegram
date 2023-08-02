<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Exception;
use Illuminate\Console\Command;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlowTelegram\DataFetchers\LongPollingDataFetcher;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramLongPollingCommand extends Command
{
    protected $signature = 'thread-flow:telegram:long-polling {channel=telegram}';

    protected $description = 'Starts long polling for Telegram bot';

    public function __construct(
        protected ThreadFlowTelegram $threadFlowTelegram,
    ) {
        parent::__construct();
    }

    /**
     * Handles the console command.
     */
    public function handle()
    {
        $channelName = $this->argument('channel');

        $config = $this->threadFlowTelegram->getTelegramChannelConfig($channelName);

        $token = $config->get('api_token');

        $dispatcherName = $config->get('dispatcher');

        $dataFetcher = new LongPollingDataFetcher($token);

        $this->output->title('ThreadFlow Telegram Long Polling');

        $this->line(
            'Channel name: <comment>' . $channelName . '</comment>'
        );

        $this->line(
            'Data fetch timeout: <comment>' . $dataFetcher->getTimeout() . ' sec</comment>'
        );

        $this->line(
            'Dispatcher: <comment>' . $dispatcherName . '</comment>'
        );

        $dataFetcher->beforeFetch($this->handleBeforeFetch(...));
        $dataFetcher->afterFetch($this->handleAfterFetch(...));
        $dataFetcher->onFetchError($this->handleFetchError(...));

        $this->threadFlowTelegram->listen(
            channelName: $channelName,
            dataFetcher: $dataFetcher,
            beforeDispatchCallback: fn(IncomingMessageInterface $message) => $this->outputLogLine(
                '<info>→ In</info>' . ($message->getStateId() ? ' <fg=blue>[BG-' . $message->getStateId(
                ) . ']</>' : '') . ': <comment>' . get_class($message) . '</comment>'
            ),
            outgoingCallback: fn(OutgoingMessageInterface $message) => $this->outputLogLine(
                '<info>← Out</info>: <comment>' . get_class($message) . '</comment>'
            )
        );
    }

    /**
     * The callback function to be executed before fetching data.
     *
     * @param int $attempt The current attempt number
     */
    protected function handleBeforeFetch(int $attempt): void
    {
        $this->line("");

        if ($attempt === 0) {
            $this->outputLogLine('Fetching updates...', '*');
            return;
        }

        $this->outputLogLine("[{$attempt} attempt] Fetching updates... ", '*');
    }

    /**
     * Outputs the given message and additional information to the console.
     *
     * @param string $message The message to output
     * @param string|null $marker An optional marker to precede the message
     * @param string|null $additionalMessage An optional additional message to append
     */
    protected function outputLogLine(
        string $message,
        ?string $marker = null,
        ?string $additionalMessage = null
    ): void {
        $this->line(
            implode(' ', [
                '',
                $marker ?? ' ',
                date('[Y-m-d H:i:s]'),
                $message,
                $additionalMessage ? "({$additionalMessage})" : ''
            ])
        );
    }

    /**
     * The callback function to be executed after data has been fetched.
     *
     * @param string $payload The payload returned by the fetch operation
     */
    protected function handleAfterFetch(string $payload): void
    {
        $parsedPayload = json_decode($payload, true);

        $isParsedOk = $parsedPayload !== null
            && json_last_error() === JSON_ERROR_NONE;

        if (! $isParsedOk) {
            $this->outputLogLine(
                "<error>Received invalid payload</error>: {$payload}"
            );
            return;
        }

        if (! isset($parsedPayload['ok']) || $parsedPayload['ok'] !== true) {
            $this->outputLogLine(
                "<error>Received data with error</error>: {$payload}"
            );
            return;
        }

        if (empty($parsedPayload['result'])) {
            $this->outputLogLine(
                'No updates received',
                ' ',
                'Transferred ' . strlen($payload) . ' bytes'
            );
            return;
        }

        $this->outputLogLine(
            '<info>Received updates</info>: '
            . '<comment>' . count($parsedPayload['result']) . '</comment>',
            ' ',
            'Transferred ' . strlen($payload) . ' bytes'
        );
    }

    /**
     * The callback function to be executed when a fetch operation results in an error.
     *
     * @param Exception $exception The exception thrown by the fetch operation
     */
    protected function handleFetchError(Exception $exception): void
    {
        $this->outputLogLine(
            "<error>Error occurred</error>: {$exception->getMessage()}"
        );
    }
}
