<?php

namespace OpenTracing\Wikia\Propagator;

use OpenTracing;
use OpenTracing\Exception\CorruptedCarrierException;
use OpenTracing\Exception\EmptyCarrierException;
use OpenTracing\Exception\InvalidCarrierException;
use OpenTracing\SplitTextCarrier;
use OpenTracing\Wikia\Span;

class SplitTextPropagator extends Propagator
{

    /**
     * Returns a Span instance with operation name $operationName
     * that's joined to the trace state embedded within $carrier, or null if
     * no such trace state could be found.
     *
     * Implementations may raise implementation-specific errors
     * if there are more fundamental problems with `carrier`.
     *
     * Upon success, the returned Span instance is already started.
     *
     * @throws EmptyCarrierException
     * @throws CorruptedCarrierException
     *
     * @param string $operationName
     * @param mixed $carrier
     * @return Span
     */
    function joinTrace($operationName, &$carrier)
    {
        if (!$carrier instanceof SplitTextCarrier) {
            throw new EmptyCarrierException();
        }
        $state = $carrier->getState();
        $baggage = $carrier->getBaggage();

        if (is_null($state) && is_null($baggage)) {
            throw new EmptyCarrierException();
        }

        if (!is_array($state) || !is_array($baggage)) {
            throw new CorruptedCarrierException();
        }
        if (!array_key_exists(self::FIELD_TRACE_ID, $state) || !array_key_exists(self::FIELD_SPAN_ID, $state)) {
            throw new CorruptedCarrierException();
        }

        $traceId = $state[self::FIELD_TRACE_ID];
        $spanId = $state[self::FIELD_SPAN_ID];

        return $this->tracer->createSpan($traceId, $spanId, $baggage);
    }

    /**
     * Takes $span and injects it into $carrier.
     *
     * The actual type of $carrier depends on the $format value passed to
     * Tracer.injector().
     *
     * Implementations may raise implementation-specific exception
     * if injection fails.
     *
     * @throws InvalidCarrierException
     *
     * @param OpenTracing\Span $span
     * @param $carrier
     * @return void
     */
    function injectSpan(OpenTracing\Span $span, &$carrier)
    {
        $this->validateSpan($span);
        if (!$carrier instanceof SplitTextCarrier) {
            throw new InvalidCarrierException();
        }

        /** @var Span $span */
        $spanData = $span->getData();
        $carrier
            ->setState([
                self::FIELD_TRACE_ID => $spanData->traceId,
                self::FIELD_SPAN_ID => $spanData->spanId,
            ])
            ->setBaggage($spanData->baggage);
    }
}