<?php

namespace kuiper\annotations;

use Symfony\Component\EventDispatcher\Event;

class AnnotationEvent extends Event
{
    /**
     * @var \ReflectionClass
     */
    private $class;

    /**
     * @var AnnotationSink
     */
    private $annotations;

    /**
     * AnnotationEvent constructor.
     *
     * @param \ReflectionClass $class
     */
    public function __construct(\ReflectionClass $class)
    {
        $this->class = $class;
    }

    /**
     * @return \ReflectionClass
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->class->getName();
    }

    /**
     * @return AnnotationSink
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * @param AnnotationSink $annotations
     *
     * @return $this
     */
    public function setAnnotations(AnnotationSink $annotations)
    {
        $this->annotations = $annotations;

        return $this;
    }
}
