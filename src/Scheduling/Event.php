<?php

namespace Thenpingme\Scheduling;

class Event
{
    public array $thenpingmeOptions = [];

    public function thenpingme(): callable
    {
        return function (array $options) {
            $this->thenpingmeOptions = $options;

            return $this;
        };
    }
}
