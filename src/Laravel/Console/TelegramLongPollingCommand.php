<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Exception;
use Illuminate\Console\Command;
use JsonException;
use SequentSoft\ThreadFlow\Contracts\BotManagerInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Incoming\IncomingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Events\Message\IncomingMessageDispatchingEvent;
use SequentSoft\ThreadFlow\Events\Message\OutgoingMessageSendingEvent;
use SequentSoft\ThreadFlowTelegram\DataFetchers\LongPollingDataFetcher;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramLongPollingCommand extends Command
{
    protected $signature = 'thread-flow:telegram:long-polling {channel=telegram}';

    protected $description = 'Starts long polling for Telegram bot';

    /**
     * Handles the console command.
     */
    public function handle(BotManagerInterface $botManager): void
    {
        $channelName = $this->argument('channel');

        $channelBot = $botManager->channel($channelName);

        $config = $channelBot->getConfig();

        $token = $config->get('api_token');

        $dispatcherName = $config->get('dispatcher');

        $dataFetcher = new LongPollingDataFetcher($token);

        $this->output->title('ThreadFlow Telegram Long Polling');

        $this->line(
            "Channel name: <comment>{$channelName}</comment>"
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

        $channelBot->on(IncomingMessageDispatchingEvent::class, function (
            IncomingMessageDispatchingEvent $event
        ) {
            $message = $event->getMessage();

            $this->outputLogLine(
                '<info>→ In</info>' . ($message->getStateId() ? ' <fg=blue>[BG-' . $message->getStateId(
                ) . ']</>' : '') . ': <comment>' . get_class($message) . '</comment>'
            );
        });

        $channelBot->on(OutgoingMessageSendingEvent::class, function (
            OutgoingMessageSendingEvent $event
        ) {
            $message = $event->getMessage();

            $this->outputLogLine(
                '<info>← Out</info>: <comment>' . get_class($message) . '</comment>'
            );
        });

        $channelBot->listen($dataFetcher);
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
     * @throws JsonException
     */
    protected function handleAfterFetch(string $payload): void
    {
        $parsedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

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
