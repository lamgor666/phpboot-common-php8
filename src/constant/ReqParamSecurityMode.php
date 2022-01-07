<?php

namespace phpboot\common\constant;

final class ReqParamSecurityMode
{
    const NONE = 0;
    const HTML_PURIFY = 1;
    const STRIP_TAGS = 2;

    private function __construct()
    {
    }
}
