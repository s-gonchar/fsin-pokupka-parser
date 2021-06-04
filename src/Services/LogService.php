<?php

namespace Services;

use Entities\Log;
use Entities\Product;
use Repositories\LogRepository;

class LogService
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    public function log(\Throwable $e = null): void
    {
        $message = $e ? $e->getMessage() : null;
        $log = new Log($message);
        $this->logRepository->persist($log);
        $this->logRepository->flush();
    }

    public function printProductMessage(Product $product): void
    {
        $externalId = $product->getExternalId();
        print_r("Product with externalId {$externalId} parsed" . PHP_EOL);
    }
}