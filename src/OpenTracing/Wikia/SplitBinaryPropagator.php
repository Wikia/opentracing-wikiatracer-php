<?php

namespace OpenTracing\Wikia;

use OpenTracing;
use OpenTracing\SplitBinaryCarrier;
use OpenTracing\Exception\EmptyCarrierException;
use OpenTracing\Exception\CorruptedCarrierException;
use OpenTracing\Exception\InvalidCarrierException;

class SplitBinaryPropagator extends Propagator
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
    public function joinTrace($operationName, &$carrier)
    {
        if (!$carrier instanceof SplitBinaryCarrier) {
            throw new EmptyCarrierException();
        }
        $state = $carrier->getState();
        $baggage = $carrier->getBaggage();

        if (is_null($state) && is_null($baggage)) {
            throw new EmptyCarrierException();
        }

        if (!is_string($state) || !is_string($baggage)) {
            throw new CorruptedCarrierException();
        }

        if (strlen($state) != 16) {
            throw new CorruptedCarrierException();
        }
        $traceId = substr($state, 0, 8);
        $spanId = substr($state, 8, 8);

        try {
            $baggage = $this->decodeArray($baggage);
        } catch (\InvalidArgumentException $e) {
            throw new CorruptedCarrierException();
        }

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
    public function injectSpan(OpenTracing\Span $span, &$carrier)
    {
        $this->validateSpan($span);
        if (!$carrier instanceof SplitBinaryCarrier) {
            throw new InvalidCarrierException();
        }

        /** @var Span $span */
        $spanData = $span->getData();
        $state = $this->formatId($spanData->traceId) . $this->formatId($spanData->spanId);
        $baggage = $this->encodeArray($spanData->baggage);

        $carrier
            ->setState($state)
            ->setBaggage($baggage);
    }

    private function formatId($id)
    {
        if (!is_string($id) || strlen($id) != 8) {
            return str_repeat(chr(0), 8);
        }

        return $id;
    }

    private function encodeArray(array $data)
    {
        $s = [];
        $s[] = pack('N', count($data));
        foreach ($data as $k => $v) {
            $k = (string)$k;
            $v = (string)$v;
            $s[] = pack('N', strlen($k));
            $s[] = $k;
            $s[] = pack('N', strlen($v));
            $s[] = $v;
        }

        return implode('', $s);
    }

    private function decodeArray($bytes)
    {
        $len = strlen($bytes);
        $pos = 0;
        $count = $this->readInt32($bytes, $len, $pos);
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $k = $this->readString($bytes, $len, $pos);
            $v = $this->readString($bytes, $len, $pos);
            $data[$k] = $v;
        }

        if ($pos != $len) {
            throw new \InvalidArgumentException('Trailing bytes found when decoding array');
        }

        return $data;
    }

    private function readString($bytes, $len, &$pos)
    {
        $count = $this->readInt32($bytes, $len, $pos);

        return $this->readBytes($bytes, $len, $count, $pos);
    }

    private function readInt32($bytes, $len, &$pos)
    {
        return unpack('N', $this->readBytes($bytes, $len, 4, $pos));
    }

    private function readBytes($bytes, $len, $count, &$pos)
    {
        if ($pos + $count > $len) {
            throw new \InvalidArgumentException(sprintf('Could not read %d bytes from byte stream', $count));
        }

        $val = substr($bytes, $pos, $count);
        $pos += $count;

        return $val;
    }

}