<?php

namespace TitasGailius\Terminal;

use Symfony\Component\Process\Process;

class OutputLine
{
    /**
     * Line type.
     *
     * @var string
     */
    protected $type;

    /**
     * Line content.
     *
     * @var string
     */
    protected $line;

    /**
     * Instantiate a new OutputLine instance.
     *
     * @param string  $type
     * @param string  $line
     */
    public function __construct(string $type, string $line)
    {
        $this->type = $type;
        $this->line = $line;
    }

    /**
     * Get the output line.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->content();
    }

    /**
     * Get the output line content.
     *
     * @return string
     */
    public function content()
    {
        return $this->line;
    }

    /**
     * Get the type of the output line.
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Determine if the output line is an error.
     *
     * @return bool
     */
    public function error()
    {
        return $this->type === Process::ERR;
    }

    /**
     * Determine if the output line is not an error.
     *
     * @return bool
     */
    public function ok()
    {
        return ! $this->error();
    }
}