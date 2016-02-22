<?php

namespace OpenTracing\Wikia;

use OpenTracing;


class Span extends OpenTracing\Span
{

    private $data = null;

    public function __construct(OpenTracing\Tracer $tracer, SpanData $data)
    {
        $this->tracer = $tracer;
        $this->data = $data;

        $this->getTracer()->getRecorder()->startSpan($this);
    }

    /**
     * Sets or changes the operation name.
     *
     * @param string $operationName
     * @return $this
     */
    public function setOperationName($operationName)
    {
        $this->data->operationName = $operationName;

        return $this;
    }

    /**
     * Indicates that the work represented by this span has been completed
     * or terminated, and is ready to be sent to the Reporter.
     *
     * If any tags / logs need to be added to the span, it should be done
     * before calling finish(), otherwise they may be ignored.
     *
     * @param float $finishTime
     */
    public function finish($finishTime = null)
    {
        $this->getTracer()->getRecorder()->finishSpan($this);
    }

    /**
     * Attaches a key/value pair to the span.
     *
     * The set of supported value types is implementation specific. It is the
     * responsibility of the actual tracing system to know how to serialize
     * and record the values.
     *
     * If the user calls set_tag multiple times for the same key,
     * the behavior of the tracer is undefined, i.e. it is implementation
     * specific whether the tracer will retain the first value, or the last
     * value, or pick one randomly, or even keep all of them.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setTag($key, $value)
    {
        $this->data->tags[$key] = $value;

        return $this;
    }

    /**
     * Logs an event against the span, with the current timestamp.
     *
     * @param string $event
     * @param array $payload
     * @return $this
     */
    public function logEvent($event, $payload = null)
    {
        $this->log(microtime(true), $event, $payload);

        return $this;
    }

    /**
     * Records a generic Log event at an arbitrary timestamp.
     *
     * @param float $timestamp
     * @param string $event
     * @param array $payload
     * @return $this
     */
    public function log($timestamp, $event, $payload = null)
    {
        $eventData = [
            'timestamp' => $timestamp,
            'event' => $event,
            'payload' => $payload,
        ];
        $this->data->logs[] = $eventData;

        $this->getTracer()->getRecorder()->log($this, $timestamp, $event, $payload);

        return $this;
    }

    /**
     * Stores Baggage in the span as a key/value pair.
     *
     * Enables powerful distributed context propagation functionality where
     * arbitrary application data can be carried along the full path of
     * request execution throughout the system.
     *
     * Note 1: baggage is only propagated to the future (recursive)
     * children of this Span.
     *
     * Note 2: baggage is sent in-band with every subsequent local and
     * remote calls, so this feature must be used with care.
     *
     * Note 3: keys are case-insensitive, to allow propagation via HTTP
     * headers. Keys must match regexp `(?i:[a-z0-9][-a-z0-9]*)`
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setBaggage($key, $value)
    {
        $this->data->baggage[$key] = $value;

        return $this;
    }

    /**
     * Retrieves value of the Baggage with the given key.
     *
     * Returns null if key doesn't exist.
     *
     * Key is case-insensitive.
     *
     * @param string $key
     * @return mixed
     */
    public function getBaggage($key)
    {
        return array_key_exists($key, $this->data->baggage) ? $this->data->baggage[$key] : null;
    }

    /**
     * Provides access to the Tracer that created this Span.
     *
     * @return Tracer
     */
    public function getTracer()
    {
        return $this->tracer;
    }

    /**
     * @return SpanData
     */
    public function getData()
    {
        return $this->data;
    }
}