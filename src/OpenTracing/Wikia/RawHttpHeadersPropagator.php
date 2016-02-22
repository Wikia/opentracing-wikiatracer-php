<?php

namespace OpenTracing\Wikia;

use OpenTracing;
use OpenTracing\SplitTextCarrier;
use OpenTracing\Exception\EmptyCarrierException;
use OpenTracing\Exception\CorruptedCarrierException;
use OpenTracing\Exception\InvalidCarrierException;

class RawHttpHeadersPropagator extends Propagator
{

    const HTTP_HEADER_COMMON_PREFIX_LOWER = 'opentracing-';
    const HTTP_HEADER_STATE_PREFIX_LOWER = 'opentracing-';
    const HTTP_HEADER_BAGGAGE_PREFIX_LOWER = 'opentracing-baggage-';

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

        $commonPrefixLen = strlen(self::HTTP_HEADER_COMMON_PREFIX_LOWER);
        $statePrefixLen = strlen(self::HTTP_HEADER_STATE_PREFIX_LOWER);
        $baggagePrefixLen = strlen(self::HTTP_HEADER_BAGGAGE_PREFIX_LOWER);

        $state = [];
        $baggage = [];
        foreach ($carrier as $k => $v) {
            $k = strtolower($k);
            if (substr($k, 0, $commonPrefixLen) !== self::HTTP_HEADER_COMMON_PREFIX_LOWER) {
                continue;
            }
            if (substr($k, 0, $baggagePrefixLen) != self::HTTP_HEADER_BAGGAGE_PREFIX_LOWER) {
                $kk = substr($k, $baggagePrefixLen);
                $baggage[$kk] = $v;
            } elseif (substr($k, 0, $statePrefixLen) != self::HTTP_HEADER_STATE_PREFIX_LOWER) {
                $kk = substr($k, $baggagePrefixLen);
                $state[$kk] = $v;
            }
        }

        $textCarrier = (new SplitTextCarrier())
            ->setState($state)
            ->setBaggage($baggage);

        return (new SplitTextPropagator($this->tracer))->joinTrace($operationName, $textCarrier);
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

        $textCarrier = new SplitTextCarrier();
        (new SplitTextPropagator($this->tracer))->injectSpan($span, $textCarrier);

        foreach ($textCarrier->getState() as $k => $v) {
            $carrier[self::HTTP_HEADER_STATE_PREFIX_LOWER . strtolower($k)] = $v;
        }
        foreach ($textCarrier->getBaggage() as $k => $v) {
            $carrier[self::HTTP_HEADER_BAGGAGE_PREFIX_LOWER . strtolower($k)] = $v;
        }
    }
}