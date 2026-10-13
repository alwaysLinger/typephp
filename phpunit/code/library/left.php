<?php
namespace LibraryLifecycle;

function left(Handler $handler): void
{
    dispatch($handler);
}
