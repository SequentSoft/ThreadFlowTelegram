<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Exception;
use Illuminate\Console\Command;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SequentSoft\ThreadFlow\Contracts\Channel\ChannelInterface;
use SequentSoft\ThreadFlow\Contracts\Config\ConfigInterface;
use SequentSoft\ThreadFlow\Contracts\DataFetchers\DataFetcherInterface;
use SequentSoft\ThreadFlow\Events\Message\IncomingMessageDispatchingEvent;
use SequentSoft\ThreadFlow\Events\Message\OutgoingMessageSentEvent;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\DataFetchers\LongPollingDataFetcher;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

use function Termwind\{render};

class TelegramLongPollingCommand extends Command
{
    protected $signature = 'threadflow:telegram-polling {--channel=telegram} {--timeout=} {--watch}';

    protected $description = 'Starts long polling for Telegram bot';

    protected ?string $latestDateOutput = null;

    protected string $currentChannelName;

    protected function getDataFetcher(ConfigInterface $config): LongPollingDataFetcher
    {
        $timeout = ((int) $this->option('timeout'))
            ?: $config->get('long_polling_timeout', 30);

        $httpClientFactory = app(HttpClientFactoryInterface::class);

        if (! $token = $config->get('api_token')) {
            throw new Exception('API token is not set');
        }

        $dataFetcher = new LongPollingDataFetcher(
            $httpClientFactory->create($token),
            $timeout,
            $config->get('long_polling_max_attempts', 3),
            $config->get('long_polling_attempt_delay', 1),
        );

        $dataFetcher->beforeFetch($this->handleBeforeFetch(...));
        $dataFetcher->afterFetch($this->handleAfterFetch(...));
        $dataFetcher->onFetchError($this->handleFetchError(...));

        return $dataFetcher;
    }

    protected function handleIncomingMessage(string $channelName, IncomingMessageDispatchingEvent $event): void
    {
        $message = $event->getMessage();

        $from = $message->getContext()?->getRoom()->getId();
        $classNameParts = explode('\\', get_class($message));
        $classNameLatestPart = array_pop($classNameParts);
        $classNamePath = implode('\\', $classNameParts);

        $pageClass = get_class($event->getPage());
        $pageClassParts = explode('\\', $pageClass);
        $pageClassName = array_pop($pageClassParts);

        render(view('threadflow-telegram::termwind.incoming', [
            'time' => date('H:i:s'),
            'from' => $from,
            'message' => $message,
            'pageClassName' => $pageClassName,
            'classNameLatestPart' => $classNameLatestPart,
            'classNamePath' => $classNamePath,
        ]));
    }

    protected function handleOutgoingMessage(string $channelName, OutgoingMessageSentEvent $event): void
    {
        $message = $event->getMessage();

        $to = $message->getContext()?->getRoom()->getId();
        $classNameParts = explode('\\', get_class($message));
        $classNameLatestPart = array_pop($classNameParts);
        $classNamePath = implode('\\', $classNameParts);

        $page = $event->getContextPage();
        $pageClass = $page ? get_class($page) : null;
        $pageClassParts = $pageClass ? explode('\\', $pageClass) : [];
        $pageClassName = $pageClass ? array_pop($pageClassParts) : null;

        render(view('threadflow-telegram::termwind.outgoing', [
            'time' => date('H:i:s'),
            'to' => $to,
            'message' => $message,
            'pageClassName' => $pageClassName,
            'classNameLatestPart' => $classNameLatestPart,
            'classNamePath' => $classNamePath,
        ]));
    }

    private function startWithWatch(ChannelInterface $channel, DataFetcherInterface $dataFetcher): void
    {
        if (! extension_loaded('pcntl') || ! extension_loaded('posix')) {
            throw new Exception(
                'The pcntl and posix extensions are required to run this command with --watch option'
            );
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, fn () => exit());

        while (true) {
            $this->handleWatch($channel, $dataFetcher);
        }
    }

    private function handleWatch(ChannelInterface $channel, DataFetcherInterface $dataFetcher)
    {
        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new Exception('Unable to fork');
        }

        if ($pid === 0) {
            // CHILD PROCESS
            pcntl_signal(SIGINT, SIG_DFL);

            $channel->listen($dataFetcher);
            return;
        }

        // PARENT PROCESS
        $path = base_path('app/ThreadFlow');
        $latestChangeTime = $this->latestFileChangeTime($path);

        while (true) {
            $status = pcntl_waitpid($pid, $status, WNOHANG);

            if ($status !== 0) {
                exit($status);
            }

            $latestChangeTimeNew = $this->latestFileChangeTime($path);

            if ($latestChangeTimeNew > $latestChangeTime) {
                render(view('threadflow-telegram::termwind.watch-changed'));

                posix_kill($pid, SIGINT);
                pcntl_waitpid($pid, $status);

                return;
            }

            usleep(1000000); // 1 second
        }

    }

    protected function latestFileChangeTime(string $path): int
    {
        $latestChangeTime = 0;

        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as $file) {
            $latestChangeTime = max($latestChangeTime, filemtime($file[0]));
        }

        return $latestChangeTime;
    }

    public function handle(ThreadFlowTelegram $channelManager): void
    {
        $this->currentChannelName = $this->option('channel');

        $channel = $channelManager->channel($this->currentChannelName);

        $config = $channel->getConfig();

        $dispatcherName = $config->get('dispatcher');

        $dataFetcher = $this->getDataFetcher($config);

        render(view('threadflow-telegram::termwind.show', [
            'channelName' => $this->currentChannelName,
            'timeout' => $dataFetcher->getTimeout(),
            'dispatcherName' => $dispatcherName,
        ]));

        $channel->on(IncomingMessageDispatchingEvent::class, $this->handleIncomingMessage(...));
        $channel->on(OutgoingMessageSentEvent::class, $this->handleOutgoingMessage(...));

        if ($this->option('watch')) {
            $this->startWithWatch($channel, $dataFetcher);
        }

        $channel->listen($dataFetcher);
    }

    /**
     * The callback function to be executed before fetching data.
     *
     * @param int $attempt The current attempt number
     */
    protected function handleBeforeFetch(int $attempt): void
    {
        $this->showDate();

        render(view('threadflow-telegram::termwind.fetching', [
            'time' => date('H:i:s'),
            'attempt' => $attempt,
        ]));
    }

    protected function showDate(): void
    {
        $date = date('Y-m-d');

        if ($this->latestDateOutput !== $date) {
            render(view('threadflow-telegram::termwind.date', [
                'date' => $date,
            ]));
            $this->latestDateOutput = $date;
        }
    }

    /**
     * The callback function to be executed after data has been fetched.
     *
     * @param string $payload The payload returned by the fetch operation
     *
     * @throws JsonException
     */
    protected function handleAfterFetch(string $payload): void
    {
        $parsedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $isParsedOk = $parsedPayload !== null
            && json_last_error() === JSON_ERROR_NONE;

        render(view('threadflow-telegram::termwind.fetched', [
            'time' => date('H:i:s'),
            'isParsedOk' => $isParsedOk,
            'payload' => $payload,
            'isOk' => $isParsedOk && isset($parsedPayload['ok']) && $parsedPayload['ok'] === true,
            'hasResult' => $isParsedOk && ! empty($parsedPayload['result']),
            'count' => $isParsedOk && isset($parsedPayload['result']) ? count($parsedPayload['result']) : 0,
            'size' => strlen($payload),
        ]));
    }

    /**
     * The callback function to be executed when a fetch operation results in an error.
     *
     * @param Exception $exception The exception thrown by the fetch operation
     */
    protected function handleFetchError(Exception $exception): void
    {
        render(view('threadflow-telegram::termwind.error', [
            'time' => date('H:i:s'),
            'exception' => $exception,
        ]));
    }
}
