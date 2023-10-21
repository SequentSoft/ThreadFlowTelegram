<?php

namespace SequentSoft\ThreadFlowTelegram\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use JsonException;
use RuntimeException;
use SequentSoft\ThreadFlow\Exceptions\Channel\ChannelNotConfiguredException;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\ThreadFlowTelegram;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'thread-flow:telegram:webhook-set {channel=telegram}';

    protected $description = 'Sets webhook for Telegram bot';

    protected function makeUrl(string $configuredWebhookUrl, string $channelName): string
    {
        $urlWithoutQueryParams = explode('?', $configuredWebhookUrl)[0];

        $queryParams = [];
        if (count($urlParts = explode('?', $configuredWebhookUrl)) > 1) {
            parse_str($urlParts[1], $queryParams);
        }

        $queryParams['channel'] = $channelName;

        return url($urlWithoutQueryParams . '?' . http_build_query($queryParams));
    }

    /**
     * Handles the console command.
     * @throws ChannelNotConfiguredException
     * @throws JsonException
     */
    public function handle(ThreadFlowTelegram $threadFlowTelegram, HttpClientFactoryInterface $httpClientFactory): void
    {
        $this->output->title('ThreadFlow Telegram Webhook Set');

        $channelName = $this->argument('channel');
        $config = $threadFlowTelegram->getTelegramChannelConfig($channelName);
        $token = $config->get('api_token');
        $webhookUrl = $config->get('webhook_url');
        $webhookIpAddress = $config->get('webhook_ip_address');
        $webhookMaxConnections = $config->get('webhook_max_connections');
        $webhookSecretToken = $config->get('webhook_secret_token');

        if (!$webhookUrl) {
            throw new RuntimeException('Webhook URL is not configured for channel "' . $channelName . '"');
        }

        $url = $this->makeUrl(
            url($webhookUrl),
            $channelName
        );

        $this->line('URL: ' . $url);

        $parsedResponse = $httpClientFactory->create($token)->postJson('setWebhook', array_filter([
            'url' => $url,
            'ip_address' => $webhookIpAddress,
            'max_connections' => $webhookMaxConnections,
            'secret_token' => $webhookSecretToken,
            'allowed_updates' => [
                'message',
                'callback_query',
                //'edited_message',
                //'channel_post',
                //'edited_channel_post',
                //'inline_query',
                //'chosen_inline_result',
                //'shipping_query',
                //'pre_checkout_query',
                //'poll',
                //'poll_answer',
                //'my_chat_member',
                //'chat_member',
            ],
        ]))->getParsedData();

        $this->line(json_encode($parsedResponse, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $this->output->success('Webhook has been set successfully');
    }
}
