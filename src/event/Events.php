<?php

namespace kuiper\di\event;

abstract class Events
{
    const BEFORE_GET_DEFINITION = 'di.before_get_definition';

    const AFTER_GET_DEFINITION = 'di.after_get_definition';

    const BEFORE_RESOLVE = 'di.before_resolve';

    const AFTER_RESOLVE = 'di.after_resolve';
}
