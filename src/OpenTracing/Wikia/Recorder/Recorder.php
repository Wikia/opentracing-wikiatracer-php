<?php

namespace OpenTracing\Wikia\Recorder;

use OpenTracing\Wikia\Span;

abstract class Recorder
{

    /**
     * @param Span $span
     * @param float $timestamp
     * @param string $event
     * @param array|null $payload
     */
    public function log(Span $span, $timestamp, $event, $payload) {
        // noop
    }

    /**
     * @param Span $span
     */
    public function startSpan(Span $span) {
        // noop
    }

    /**
     * @param Span $span
     */
    public function finishSpan(Span $span) {
        // noop
    }
}