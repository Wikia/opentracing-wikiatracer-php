<?php

namespace OpenTracing\Wikia\Propagator;

use OpenTracing;
use OpenTracing\Extractor;
use OpenTracing\Injector;
use OpenTracing\Wikia\Tracer;
use OpenTracing\Wikia\Span;


abstract class Propagator implements Injector, Extractor
{

    const FIELD_STATE = 'state';
    const FIELD_BAGGAGE = 'baggage';
    const FIELD_TRACE_ID = 'traceid';
    const FIELD_SPAN_ID = 'spanid';

    protected $tracer = null;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    protected function validateSpan(OpenTracing\Span $span)
    {
        if (!$span instanceof Span) {
            throw new \InvalidArgumentException('Unsupported Span object provided');
        }
    }
}