<?php
namespace LibraryLifecycle;

function right(Handler $handler): void
{
    dispatch($handler);
}
