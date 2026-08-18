<?php

namespace App\Enums;

enum ImportMode: string
{
    case InsertOnly = 'insert_only';
    case UpdateExisting = 'update_existing';
}
