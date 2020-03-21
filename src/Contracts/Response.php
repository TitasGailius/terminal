<?php

namespace TitasGailius\Terminal\Contracts;

use IteratorAggregate;

interface Response extends IteratorAggregate
{
    /**
     * Check if the process ended successfully.
     *
     * @return boolean
     */
    public function ok();

    /**
     * Check if the process ended successfully.
     *
     * @return boolean
     */
    public function successful();

    /**
     * Get the process output.
     *
     * @return string
     */
    public function output();

    /**
     * Get the process output.
     *
     * @return string
     */
    public function __toString();

    /**
     * Return an array of outputed lines.
     *
     * @return array
     */
    public function lines();

    /**
     * Throw an exception if the process was not successful.
     *
     * @return $this
     *
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function throw();

    /**
     * Get the underlying process instance.
     *
     * @return \Symfony\Component\Process\Process
     */
    public function process();
}