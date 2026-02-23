<?php


namespace App\SlackSchema;

class SlackSchemaBuilder
{
    public static function text(&$blocks, $content)
    {
        return $blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'plain_text',
                'text' => $content,
                'emoji' => true,
            ],
        ];
    }

    public static function markdown(&$blocks, $content)
    {
        return $blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => str_replace("\\n", "\n", $content),
            ],
        ];
    }

    public static function divider(&$blocks)
    {
        return $blocks[] = [
            'type' => 'divider',
        ];
    }

    public static function button(&$blocks, $buttons)
    {
        $elements = [];
        $counter = 1;
        foreach ($buttons as $key => $button) {
            $elements[] = [
                'type' => 'button',
                'value' => 'button_' . $counter,
                'text' => [
                    'type' => 'plain_text',
                    'text' => $button['label'],
                    'emoji' => true,
                ],
                'url' => $button['link'],
                'style' => isset($button['style']) ?: 'primary',
            ];
            $counter++;
        }

        return $blocks[] = [
            'type' => 'actions',
            'elements' => $elements,
        ];
    }

    public static function createButtons(array $buttonsData): array
    {
        $buttons = [];

        foreach ($buttonsData as $data) {
            // Skip invalid or empty entries
            if (empty($data['label']) || empty($data['link'])) {
                continue;
            }

            $buttons[] = [
                'label' => $data['label'],
                'link' => $data['link'],
            ];
        }

        return $buttons;
    }
}
