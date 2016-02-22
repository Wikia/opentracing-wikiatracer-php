<?php

namespace OpenTracing\Wikia;

class SpanData
{
    /**
     * @var string
     */
    public $traceId = null;
    /**
     * @var string
     */
    public $spanId = null;
    /**
     * @var string|null
     */
    public $parentSpanId = null;

    /**
     * @var string
     */
    public $operationName = null;

    /**
     * @var float
     */
    public $startTime = null;
    /**
     * @var float|null
     */
    public $finishTime = null;

    /**
     * @var array
     */
    public $tags = [];
    /**
     * @var array
     */
    public $baggage = [];
    /**
     * @var array
     */
    public $logs = [];
}