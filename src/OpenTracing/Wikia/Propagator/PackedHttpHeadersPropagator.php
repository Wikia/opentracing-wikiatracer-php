<?php

namespace OpenTracing\Wikia\Propagator;

use OpenTracing;
use OpenTracing\SplitBinaryCarrier;
use OpenTracing\Exception\EmptyCarrierException;
use OpenTracing\Exception\CorruptedCarrierException;
use OpenTracing\Exception\InvalidCarrierException;
use OpenTracing\Wikia\Span;

class PackedHttpHeadersPropagator extends Propagator
{

    const HTTP_HEADER_STATE_LOWER = 'opentracing-state';
    const HTTP_HEADER_BAGGAGE_LOWER = 'opentracing-baggage';

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
     * @throws InvalidCarrierException
     *
     * @param string $operationName
     * @param mixed $carrier
     * @return Span
     */
    public function joinTrace($operationName, &$carrier)
    {
        if (!is_array($carrier)) {
            throw new InvalidCarrierException();
        }

        $state = null;
        $baggage = null;

        foreach ($carrier as $k => $v) {
            $k = strtolower($k);
            switch ($k) {
                case self::HTTP_HEADER_STATE_LOWER:
                    $state = base64_decode($v);
                    break;
                case self::HTTP_HEADER_BAGGAGE_LOWER:
                    $baggage = base64_decode($v);
                    break;
            }
        }

        $binaryCarrier = (new SplitBinaryCarrier())
            ->setState($state)
            ->setBaggage($baggage);

        return (new SplitBinaryPropagator($this->tracer))->joinTrace($operationName, $binaryCarrier);
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
     * @param mixed $carrier
     * @return void
     */
    public function injectSpan(OpenTracing\Span $span, &$carrier)
    {
        $this->validateSpan($span);
        if (!is_array($carrier)) {
            throw new InvalidCarrierException();
        }

        $binaryCarrier = new SplitBinaryCarrier();
        (new SplitBinaryPropagator($this->tracer))->injectSpan($span, $binaryCarrier);

        $carrier[self::HTTP_HEADER_STATE_LOWER] = base64_encode($binaryCarrier->getState());
        $carrier[self::HTTP_HEADER_BAGGAGE_LOWER] = base64_encode($binaryCarrier->getBaggage());
    }
}