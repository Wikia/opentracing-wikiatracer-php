<?php

namespace OpenTracing\Wikia\Recorder;

use OpenTracing\Wikia\Span;

class Recorder
{
    private $recorders;

    public function __construct(array $recorders)
    {
        $this->setRecorders($recorders);
    }

    public function setRecorders(array $recorders)
    {
        $this->recorders = $recorders;
    }

    public function log(Span $span, $timestamp, $event, $payload)
    {
        /** @var Recorder $recorder */
        foreach ($this->recorders as $recorder) {
            $recorder->log($span, $timestamp, $event, $payload);
        }
    }
}