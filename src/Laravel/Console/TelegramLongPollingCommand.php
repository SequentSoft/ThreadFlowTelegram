<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Exception;
use Illuminate\Console\Command;
use JsonException;
use SequentSoft\ThreadFlow\Config;
use SequentSoft\ThreadFlow\Contracts\Channel\ChannelManagerInterface;
use SequentSoft\ThreadFlow\Events\Message\IncomingMessageDispatchingEvent;
use SequentSoft\ThreadFlow\Events\Message\OutgoingMessageSendingEvent;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\DataFetchers\LongPollingDataFetcher;

class TelegramLongPollingCommand extends Command
{
    protected $signature = 'threadflow:telegram-polling {--channel=telegram} {--timeout=}';

    protected $description = 'Starts long polling for Telegram bot';

    protected ?string $latestDateOutput = null;

    protected string $currentChannelName;

    /**
     * Handles the console command.
     */
    public function handle(ChannelManagerInterface $channelManager, HttpClientFactoryInterface $httpClientFactory): void
    {
        $this->currentChannelName = $this->option('channel');

        $rawConfig = config("thread-flow.channels.{$this->currentChannelName}", []);

        if (! $rawConfig) {
            $this->output->error("Channel '{$this->currentChannelName}' is not configured");
            return;
        }

        $config = new Config($rawConfig);

        $token = $config->get('api_token');

        // TODO: show error if token is not set

        $dispatcherName = $config->get('dispatcher');

        $timeout = ((int) $this->option('timeout')) ?: $config->get('long_polling_timeout', 30);

        $dataFetcher = new LongPollingDataFetcher(
            $httpClientFactory->create($token),
            $timeout,
            $config->get('long_polling_max_attempts', 3),
            $config->get('long_polling_attempt_delay', 1),
        );

        $this->output->title('ThreadFlow Telegram Long Polling');

        $this->getOutput()->listing([
            "Channel name: <comment>{$this->currentChannelName}</comment>",
            'Data fetch timeout: <comment>' . $dataFetcher->getTimeout() . ' sec</comment>',
            'Dispatcher: <comment>' . $dispatcherName . '</comment>',
        ]);

        $dataFetcher->beforeFetch($this->handleBeforeFetch(...));
        $dataFetcher->afterFetch($this->handleAfterFetch(...));
        $dataFetcher->onFetchError($this->handleFetchError(...));


        $channelManager->on(IncomingMessageDispatchingEvent::class, function (
            string $channelName,
            IncomingMessageDispatchingEvent $event
        ) {
            $message = $event->getMessage();

            $from = $message->getContext()?->getRoom()->getId();
            $classNameParts = explode('\\', get_class($message));
            $classNameLatestPart = array_pop($classNameParts);
            $classNamePath = implode('\\', $classNameParts);

            $this->outputLogLine(
                $channelName,
                implode(
                    ' ',
                    array_filter([
                        '<info>→ In:</info>',
                        "{$classNamePath}\\\033[33m{$classNameLatestPart}\033[0m",
                        ($from ? "#FROM:{$from}" : ''),
                        ($message->getPageId() ? "#SID:" . $message->getPageId() : ''),
                    ])
                )
            );
        });

        $channelManager->on(OutgoingMessageSendingEvent::class, function (
            string $channelName,
            OutgoingMessageSendingEvent $event
        ) {
            $message = $event->getMessage();

            $to = $message->getContext()?->getRoom()->getId();
            $classNameParts = explode('\\', get_class($message));
            $classNameLatestPart = array_pop($classNameParts);
            $classNamePath = implode('\\', $classNameParts);

            $this->outputLogLine(
                $channelName,
                implode(
                    ' ',
                    array_filter([
                        '<info>← Out:</info>',
                        "{$classNamePath}\\\033[33m{$classNameLatestPart}\033[0m",
                        ($to ? "#TO:{$to}" : ''),
                    ])
                )
            );
        });

        $channelManager->channel($this->currentChannelName)->listen($dataFetcher);
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
            $this->outputLogLine(
                $this->currentChannelName,
                'Fetching updates...',
                '*'
            );
            return;
        }

        $this->outputLogLine(
            $this->currentChannelName,
            "[{$attempt} attempt] Fetching updates... ",
            '*'
        );
    }

    protected function showDate(): void
    {
        $date = date('Y-m-d');

        if ($this->latestDateOutput !== $date) {
            $this->getOutput()->section('Date: ' . $date);
            $this->latestDateOutput = $date;
        }
    }

    /**
     * Outputs the given message and additional information to the console.
     *
     * @param string $message The message to output
     * @param string|null $marker An optional marker to precede the message
     * @param string|null $additionalMessage An optional additional message to append
     */
    protected function outputLogLine(
        string $channelName,
        string $message,
        ?string $marker = null,
        ?string $additionalMessage = null
    ): void {
        $this->showDate();

        $this->line(
            implode(' ', [
                '',
                $marker ?? ' ',
                date('H:i:s') . ' |',
                ($channelName === $this->currentChannelName ? $channelName : "<fg=red>{$channelName}</>") . ' |',
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
                $this->currentChannelName,
                "<error>Received invalid payload</error>: {$payload}"
            );
            return;
        }

        if (! isset($parsedPayload['ok']) || $parsedPayload['ok'] !== true) {
            $this->outputLogLine(
                $this->currentChannelName,
                "<error>Received data with error</error>: {$payload}"
            );
            return;
        }

        if (empty($parsedPayload['result'])) {
            $this->outputLogLine(
                $this->currentChannelName,
                'No updates received',
                ' ',
                'Transferred ' . strlen($payload) . ' bytes'
            );
            return;
        }

        $this->outputLogLine(
            $this->currentChannelName,
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
            $this->currentChannelName,
            "<error>Error occurred</error>: {$exception->getMessage()}"
        );
    }
}
