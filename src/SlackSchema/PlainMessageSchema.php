<?php

namespace App\SlackSchema;

class PlainMessageSchema
{
    public static function get($message): bool|string
    {
        $blocks = [];

        SlackSchemaBuilder::markdown($blocks, $message);

        return json_encode([
            'blocks' => $blocks,
        ]);
    }

}