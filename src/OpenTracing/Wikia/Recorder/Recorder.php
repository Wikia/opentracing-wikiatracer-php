<?php

namespace OpenTracing\Wikia\Recorder;

use OpenTracing\Wikia\Span;

abstract class Recorder
{

    public function log(Span $span, $timestamp, $event, $payload) {
        // noop
    }
}