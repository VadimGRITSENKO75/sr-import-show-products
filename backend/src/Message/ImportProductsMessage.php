<?php

namespace App\Message;

final class ImportProductsMessage
{
    public function __construct(
        public readonly string $filePath,
        public readonly string $taskId,
    ) {
    }
}
