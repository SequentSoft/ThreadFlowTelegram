<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\DataFetchers;

use Closure;

interface DataFetcherInterface
{
    public function fetch(Closure $handleUpdate): void;
}
