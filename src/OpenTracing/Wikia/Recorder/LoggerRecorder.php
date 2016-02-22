<?php

namespace OpenTracing\Wikia\Recorder;

use OpenTracing\Wikia\Span;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerRecorder extends Recorder
{
    const DEFAULT_LEVEL = LogLevel::INFO;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct( LoggerInterface $logger ) {
        $this->logger = $logger;
    }

    /**
     * @param Span $span
     * @param float $timestamp
     * @param string $event
     * @param array|null $payload
     */
    public function log(Span $span, $timestamp, $event, $payload) {
        $spanData = $span->getData();
        $context = array_merge(
            $spanData->attributes,
            $payload ?: [],
            [
                'timestamp' => $timestamp,
                'trace_id' => $spanData->traceId,
                'span_id' => $spanData->spanId,
                'span_name' => $spanData->operationName,
            ]);

        $level = @$payload['severity'] ?: self::DEFAULT_LEVEL;
        $this->logger->log($level, $event, $context);
    }

    /**
     * @param Span $span
     */
    public function startSpan(Span $span) {
        $this->log($span, microtime(true), 'Span started.', []);
    }

    /**
     * @param Span $span
     */
    public function finishSpan(Span $span) {
        $this->log($span, microtime(true), 'Span finished.', []);
    }
}