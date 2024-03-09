<?php

namespace App\Contracts;

interface LabCommand
{
    public function runLab($internal = false): void;
}
