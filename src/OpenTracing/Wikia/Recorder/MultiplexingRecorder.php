<?php

namespace OpenTracing\Wikia\Recorder;

use OpenTracing\Wikia\Span;

class MultiplexingRecorder extends Recorder
{
    /**
     * @var Recorder[]
     */
    private $recorders;

    /**
     * @param Recorder[] $recorders
     */
    public function __construct(array $recorders)
    {
        $this->setRecorders($recorders);
    }

    /**
     * @param Recorder[] $recorders
     */
    public function setRecorders(array $recorders)
    {
        $this->recorders = $recorders;
    }

    /**
     * @param Recorder $recorder
     */
    public function addRecorder(Recorder $recorder)
    {
        $this->recorders[] = $recorder;
    }

    /**
     * @param Span $span
     * @param float $timestamp
     * @param string $event
     * @param array|null $payload
     */
    public function log(Span $span, $timestamp, $event, $payload)
    {
        foreach ($this->recorders as $recorder) {
            $recorder->log($span, $timestamp, $event, $payload);
        }
    }

    /**
     * @param Span $span
     */
    public function startSpan(Span $span) {
        foreach ($this->recorders as $recorder) {
            $recorder->startSpan($span);
        }
    }

    /**
     * @param Span $span
     */
    public function finishSpan(Span $span) {
        foreach ($this->recorders as $recorder) {
            $recorder->finishSpan($span);
        }
    }
}