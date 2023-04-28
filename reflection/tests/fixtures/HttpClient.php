<?php

namespace kuiper\reflection\fixtures;

interface HttpClient
{
    /**
     * @return array<string, mixed>
     */
    public function getUsers(): array;
}