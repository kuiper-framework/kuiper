<?php

namespace kuiper\rpc\server;

interface ErrorInterceptorInterface
{
    /**
     * Handle method invoking error.
     *
     * @param ErrorContext $error
     *
     * @return mixed
     */
    public function handle(ErrorContext $error);
}
