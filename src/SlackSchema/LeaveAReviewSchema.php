<?php
namespace  App\SlackSchema;

use App\SlackSchema\SlackSchemaBuilder;

class LeaveAReviewSchema
{
    public static function get(array $data): string
    {
        $blocks = [];

        SlackSchemaBuilder::markdown($blocks, "*Leave a Review Cron Report*");
        $blocks[] = ['type' => 'divider'];

        $blocks[] = [
            'type' => 'section',
            'fields' => [
                ['type' => 'mrkdwn', 'text' => "*Date:* {$data['date']}"],
                ['type' => 'mrkdwn', 'text' => "*Type:* {$data['type']}"],
                ['type' => 'mrkdwn', 'text' => "*Total Orders:* {$data['totalOrders']}"],
                ['type' => 'mrkdwn', 'text' => "*Emails Sent:* {$data['emailsSent']}"],
            ],
        ];
        $blocks[] = ['type' => 'divider'];

        if (!empty($data['errors'])) {
            SlackSchemaBuilder::markdown($blocks, "*:warning: Errors Found :warning:*");
            foreach ($data['errors'] as $error) {
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Order ID:* {$error['orderId']}\n*Error:* {$error['error']}",
                    ],
                ];
                $blocks[] = ['type' => 'divider'];
            }
        } else {
            SlackSchemaBuilder::markdown($blocks, ":white_check_mark: No errors found!");
        }

        $blocks[] = [
            'type' => 'context',
            'elements' => [
                ['type' => 'mrkdwn', 'text' => "_Generated automatically by the Leave a Review Cron._"],
            ],
        ];

        return json_encode([
            'blocks' => $blocks,
        ]);
    }
}
