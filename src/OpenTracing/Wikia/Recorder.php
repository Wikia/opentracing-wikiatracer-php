<?php

namespace OpenTracing\Wikia;

abstract class Recorder
{

    public function log(Span $span, $timestamp, $event, $payload) {
        // noop
    }
}