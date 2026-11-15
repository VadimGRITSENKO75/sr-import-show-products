<?php

namespace App\MessageHandler;

use App\Message\ImportProductsMessage;
use App\Service\ImportService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ImportProductsHandler
{
    public function __construct(
        private ImportService $importService
    ) {
    }

    public function __invoke(ImportProductsMessage $message): void
    {
        $this->importService->importFromFile($message->filePath, $message->taskId);
    }
}
